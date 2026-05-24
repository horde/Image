<?php

declare(strict_types=1);

namespace Horde\Image\Metadata\Parser;

final class SanyoParser implements MakerNoteParser
{
    public function supports(string $makeLower): bool
    {
        return str_contains($makeLower, 'sanyo');
    }

    public function parse(string $data, bool $intel): array
    {
        $result = [];
        $length = strlen($data);
        $headerOffset = 0;

        if ($length < 2) {
            return $result;
        }

        $count = $this->readShort($data, $headerOffset, $intel);
        $offset = $headerOffset + 2;

        for ($i = 0; $i < $count && ($offset + 12) <= $length; $i++) {
            $tag = $this->readShort($data, $offset, $intel);
            $type = $this->readShort($data, $offset + 2, $intel);
            $numValues = $this->readLong($data, $offset + 4, $intel);
            $valueOffset = $offset + 8;

            $byteCount = $this->typeSize($type) * $numValues;
            if ($byteCount > 4) {
                $valueOffset = $this->readLong($data, $offset + 8, $intel);
            }

            $name = $this->tagName($tag);
            if ($name !== null && $valueOffset >= 0 && $valueOffset + min($byteCount, 256) <= $length) {
                if ($type === 2) {
                    $result[$name] = rtrim(substr($data, $valueOffset, min($numValues, 256)), "\x00");
                } elseif ($type === 3 && $numValues === 1) {
                    $result[$name] = $this->readShort($data, $valueOffset, $intel);
                }
            }

            $offset += 12;
        }

        return $result;
    }

    private function tagName(int $tag): ?string
    {
        return match ($tag) {
            0x0100 => 'Sanyo:ThumbnailOffset',
            0x0200 => 'Sanyo:SpecialMode',
            0x0201 => 'Sanyo:Quality',
            0x0202 => 'Sanyo:Macro',
            0x0204 => 'Sanyo:DigitalZoom',
            default => null,
        };
    }

    private function readShort(string $data, int $offset, bool $intel): int
    {
        $bytes = substr($data, $offset, 2);
        if (strlen($bytes) < 2) {
            return 0;
        }
        $unpacked = $intel ? unpack('v', $bytes) : unpack('n', $bytes);
        return $unpacked !== false ? $unpacked[1] : 0;
    }

    private function readLong(string $data, int $offset, bool $intel): int
    {
        $bytes = substr($data, $offset, 4);
        if (strlen($bytes) < 4) {
            return 0;
        }
        $unpacked = $intel ? unpack('V', $bytes) : unpack('N', $bytes);
        return $unpacked !== false ? $unpacked[1] : 0;
    }

    private function typeSize(int $type): int
    {
        return match ($type) {
            1, 2, 6, 7 => 1,
            3, 8 => 2,
            4, 9, 11 => 4,
            5, 10, 12 => 8,
            default => 1,
        };
    }
}
