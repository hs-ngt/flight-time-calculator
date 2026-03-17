<?php
declare(strict_types=1);

final class Validator
{
    /**
     * @param array<string, mixed> $input
     * @return array<int, string>
     */
    public function validateCalculationInput(array $input, CityRepository $repo): array
    {
        $errors = [];

        $requiredKeys = [
            'from_id' => '出発地点を選択してください。',
            'to_id' => '到着地点を選択してください。',
            'departure_date' => '出発日を入力してください。',
            'departure_time' => '出発時刻を入力してください。',
            'arrival_date' => '到着日を入力してください。',
            'arrival_time' => '到着時刻を入力してください。',
        ];

        foreach ($requiredKeys as $key => $message) {
            if (!isset($input[$key]) || trim((string) $input[$key]) === '') {
                $errors[] = $message;
            }
        }

        if ($errors !== []) {
            return $errors;
        }

        if ($repo->findById((string) $input['from_id']) === null) {
            $errors[] = '出発地点が見つかりません。候補から選び直してください。';
        }

        if ($repo->findById((string) $input['to_id']) === null) {
            $errors[] = '到着地点が見つかりません。候補から選び直してください。';
        }

        if (!$this->isValidDate((string) $input['departure_date'])) {
            $errors[] = '出発日の形式が正しくありません。';
        }

        if (!$this->isValidDate((string) $input['arrival_date'])) {
            $errors[] = '到着日の形式が正しくありません。';
        }

        if (!$this->isValidTime((string) $input['departure_time'])) {
            $errors[] = '出発時刻の形式が正しくありません。';
        }

        if (!$this->isValidTime((string) $input['arrival_time'])) {
            $errors[] = '到着時刻の形式が正しくありません。';
        }

        return $errors;
    }

    private function isValidDate(string $value): bool
    {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return $dt instanceof DateTimeImmutable && $dt->format('Y-m-d') === $value;
    }

    private function isValidTime(string $value): bool
    {
        $dt = DateTimeImmutable::createFromFormat('H:i', $value);
        return $dt instanceof DateTimeImmutable && $dt->format('H:i') === $value;
    }
}
