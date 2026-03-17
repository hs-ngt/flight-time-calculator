<?php
declare(strict_types=1);

final class CityRepository
{
    /** @var array<int, array<string, mixed>> */
    private array $cities;

    public function __construct(private string $filePath)
    {
        if (!is_file($this->filePath)) {
            throw new RuntimeException('City data file was not found.');
        }

        $raw = file_get_contents($this->filePath);
        if ($raw === false) {
            throw new RuntimeException('Failed to read city data file.');
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('City data file is invalid.');
        }

        $this->cities = $decoded;
    }

    /** @return array<int, array<string, mixed>> */
    public function getAll(): array
    {
        return $this->cities;
    }

    /** @return array<string, mixed>|null */
    public function findById(string $id): ?array
    {
        foreach ($this->cities as $city) {
            if (($city['id'] ?? '') === $id) {
                return $city;
            }
        }

        return null;
    }

    /** @return array<int, array<string, mixed>> */
    public function getPublicList(): array
    {
        $items = [];

        foreach ($this->cities as $city) {
            $items[] = [
                'id' => $city['id'],
                'label' => $city['label_ja'],
                'label_en' => $city['label_en'],
                'country_name' => $city['country_name_ja'],
                'country_name_en' => $city['country_name_en'],
                'timezone_id' => $city['timezone_id'],
                'aliases' => $city['aliases'],
                'sort_weight' => $city['sort_weight'] ?? 0,
            ];
        }

        usort(
            $items,
            static fn(array $a, array $b): int => ($b['sort_weight'] <=> $a['sort_weight']) ?: strcmp((string) $a['label'], (string) $b['label'])
        );

        return $items;
    }
}
