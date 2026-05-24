<?php

declare(strict_types=1);

namespace Horde\Image\Metadata\Parser;

final class PanasonicParser implements MakerNoteParser
{
    public function supports(string $makeLower): bool
    {
        return str_contains($makeLower, 'panasonic') || str_contains($makeLower, 'lumix');
    }

    public function parse(string $data, bool $intel): array
    {
        $result = [];
        $length = strlen($data);
        $headerOffset = 0;

        if (str_starts_with($data, "Panasonic\x00")) {
            $headerOffset = 12;
        }

        if ($headerOffset + 2 > $length) {
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
                $valueOffset = $this->readLong($data, $offset + 8, $intel) + $headerOffset;
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
            0x0001 => 'Panasonic:Quality',
            0x0003 => 'Panasonic:WhiteBalance',
            0x0007 => 'Panasonic:FocusMode',
            0x000F => 'Panasonic:AFMode',
            0x001A => 'Panasonic:ImageStabilization',
            0x001C => 'Panasonic:Macro',
            0x001F => 'Panasonic:ShootingMode',
            0x0020 => 'Panasonic:Audio',
            0x0023 => 'Panasonic:WhiteBalanceBias',
            0x0024 => 'Panasonic:FlashBias',
            0x0025 => 'Panasonic:InternalSerialNumber',
            0x0028 => 'Panasonic:ColorEffect',
            0x002A => 'Panasonic:BurstMode',
            0x002C => 'Panasonic:Contrast',
            0x002D => 'Panasonic:NoiseReduction',
            0x002E => 'Panasonic:SelfTimer',
            0x0030 => 'Panasonic:Rotation',
            0x0032 => 'Panasonic:ColorMode',
            0x0051 => 'Panasonic:LensType',
            0x0052 => 'Panasonic:LensSerialNumber',
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
