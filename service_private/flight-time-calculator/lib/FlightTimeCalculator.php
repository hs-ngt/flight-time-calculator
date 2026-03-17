<?php

declare(strict_types=1);

final class FlightTimeCalculator
{
    /**
     * @param array<string, mixed> $fromCity
     * @param array<string, mixed> $toCity
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function calculate(array $fromCity, array $toCity, array $input): array
    {
        $fromTimezone = $this->createTimezone((string) $fromCity['timezone_id']);
        $toTimezone = $this->createTimezone((string) $toCity['timezone_id']);

        $departureLocal = $this->createLocalDateTime(
            (string) $input['departure_date'],
            (string) $input['departure_time'],
            $fromTimezone
        );

        $arrivalLocal = $this->createLocalDateTime(
            (string) $input['arrival_date'],
            (string) $input['arrival_time'],
            $toTimezone
        );

        $departureUtc = $departureLocal->setTimezone(new DateTimeZone('UTC'));
        $arrivalUtc = $arrivalLocal->setTimezone(new DateTimeZone('UTC'));

        $durationSeconds = $arrivalUtc->getTimestamp() - $departureUtc->getTimestamp();
        if ($durationSeconds < 0) {
            throw new InvalidArgumentException('到着日時が出発日時より前になっています。入力内容を確認してください。');
        }

        $fromOffsetSeconds = $fromTimezone->getOffset($departureLocal);
        $toOffsetSeconds = $toTimezone->getOffset($arrivalLocal);
        $timezoneDiffMinutes = intdiv($toOffsetSeconds - $fromOffsetSeconds, 60);

        $arrivalDayOffset = $this->calculateDayOffset(
            (string) $input['departure_date'],
            (string) $input['arrival_date']
        );

        return [
            'duration_minutes' => intdiv($durationSeconds, 60),
            'duration_text' => $this->formatDuration($durationSeconds),
            'timezone_diff_minutes' => $timezoneDiffMinutes,
            'timezone_diff_text' => $this->formatTimezoneDiff($timezoneDiffMinutes),
            'departure_local_text' => $departureLocal->format('Y-m-d H:i'),
            'arrival_local_text' => $arrivalLocal->format('Y-m-d H:i'),
            'departure_utc_text' => $departureUtc->format('Y-m-d H:i') . ' UTC',
            'arrival_utc_text' => $arrivalUtc->format('Y-m-d H:i') . ' UTC',
            'from_city_label' => $fromCity['label_ja'],
            'to_city_label' => $toCity['label_ja'],
            'from_timezone_id' => $fromCity['timezone_id'],
            'to_timezone_id' => $toCity['timezone_id'],
            'from_offset_text' => $this->formatOffset($fromOffsetSeconds),
            'to_offset_text' => $this->formatOffset($toOffsetSeconds),
            'from_dst_text' => $this->formatDstStatus($departureLocal, $fromTimezone),
            'to_dst_text' => $this->formatDstStatus($arrivalLocal, $toTimezone),
            'arrival_day_offset' => $arrivalDayOffset,
            'arrival_day_offset_text' => $this->formatDayOffset($arrivalDayOffset),
        ];
    }

    private function createTimezone(string $timezoneId): DateTimeZone
    {
        if (!in_array($timezoneId, DateTimeZone::listIdentifiers(), true)) {
            throw new InvalidArgumentException('timezone_id が不正です。');
        }

        return new DateTimeZone($timezoneId);
    }

    private function createLocalDateTime(string $date, string $time, DateTimeZone $timezone): DateTimeImmutable
    {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i', $date . ' ' . $time, $timezone);
        if (!$dt instanceof DateTimeImmutable) {
            throw new InvalidArgumentException('日時の生成に失敗しました。');
        }

        return $dt;
    }

    private function formatDuration(int $durationSeconds): string
    {
        $totalMinutes = intdiv($durationSeconds, 60);
        $days = intdiv($totalMinutes, 1440);
        $restMinutesAfterDays = $totalMinutes % 1440;
        $hours = intdiv($restMinutesAfterDays, 60);
        $minutes = $restMinutesAfterDays % 60;

        $parts = [];
        if ($days > 0) {
            $parts[] = $days . '日';
        }
        if ($hours > 0 || $days > 0) {
            $parts[] = $hours . '時間';
        }
        $parts[] = $minutes . '分';

        return implode('', $parts);
    }

    private function formatTimezoneDiff(int $minutes): string
    {
        $sign = $minutes > 0 ? '+' : '';
        $abs = abs($minutes);
        $hours = intdiv($abs, 60);
        $rest = $abs % 60;

        if ($rest === 0) {
            return sprintf('%s%d時間', $sign, $minutes < 0 ? -$hours : $hours);
        }

        return sprintf('%s%d時間%02d分', $sign, $minutes < 0 ? -$hours : $hours, $rest);
    }

    private function formatOffset(int $seconds): string
    {
        $sign = $seconds >= 0 ? '+' : '-';
        $abs = abs($seconds);
        $hours = intdiv($abs, 3600);
        $minutes = intdiv($abs % 3600, 60);

        return sprintf('UTC%s%02d:%02d', $sign, $hours, $minutes);
    }


    private function formatDstStatus(DateTimeImmutable $dateTime, DateTimeZone $timezone): string
    {
        $isDst = $dateTime->format('I') === '1';
        $hasDstTransitions = $this->timezoneHasDstTransitionsForYear($timezone, (int) $dateTime->format('Y'));

        return match (true) {
            $isDst => '実施中',
            $hasDstTransitions => '現在は通常時間',
            default => '対象外',
        };
    }

    private function timezoneHasDstTransitionsForYear(DateTimeZone $timezone, int $year): bool
    {
        $start = (new DateTimeImmutable(sprintf('%04d-01-01 00:00:00', $year), new DateTimeZone('UTC')))->getTimestamp();
        $end = (new DateTimeImmutable(sprintf('%04d-12-31 23:59:59', $year), new DateTimeZone('UTC')))->getTimestamp();
        $transitions = $timezone->getTransitions($start, $end);

        if (!is_array($transitions) || count($transitions) < 2) {
            return false;
        }

        $seenDst = false;
        $seenStandard = false;

        foreach ($transitions as $transition) {
            if (!isset($transition['isdst'])) {
                continue;
            }

            if ((bool) $transition['isdst']) {
                $seenDst = true;
            } else {
                $seenStandard = true;
            }

            if ($seenDst && $seenStandard) {
                return true;
            }
        }

        return false;
    }

    private function calculateDayOffset(string $departureDate, string $arrivalDate): int
    {
        $dep = DateTimeImmutable::createFromFormat('Y-m-d', $departureDate);
        $arr = DateTimeImmutable::createFromFormat('Y-m-d', $arrivalDate);

        if (!$dep instanceof DateTimeImmutable || !$arr instanceof DateTimeImmutable) {
            return 0;
        }

        return (int) $dep->diff($arr)->format('%r%a');
    }

    private function formatDayOffset(int $offset): string
    {
        return match (true) {
            $offset === 0 => '同日到着',
            $offset === 1 => '翌日到着',
            $offset > 1 => '+' . $offset . '日で到着',
            $offset === -1 => '前日到着',
            default => (string) $offset . '日で到着',
        };
    }
}
