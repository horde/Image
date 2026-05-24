<?php

declare(strict_types=1);

namespace Horde\Image\Metadata\Parser;

final class NikonParser implements MakerNoteParser
{
    public function supports(string $makeLower): bool
    {
        return str_contains($makeLower, 'nikon');
    }

    public function parse(string $data, bool $intel): array
    {
        $result = [];
        $length = strlen($data);

        if ($length < 10) {
            return $result;
        }

        $headerOffset = 0;
        if (str_starts_with($data, "Nikon\x00")) {
            $version = ord($data[6]);
            if ($version === 2 && $length > 18) {
                $headerOffset = 10;
                $byteOrder = substr($data, 10, 2);
                $intel = ($byteOrder === "II");
                $headerOffset = 18;
            } else {
                $headerOffset = 8;
            }
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
                } elseif ($type === 4 && $numValues === 1) {
                    $result[$name] = $this->readLong($data, $valueOffset, $intel);
                }
            }

            $offset += 12;
        }

        return $result;
    }

    private function tagName(int $tag): ?string
    {
        return match ($tag) {
            0x0001 => 'Nikon:MakerNoteVersion',
            0x0002 => 'Nikon:ISO',
            0x0004 => 'Nikon:Quality',
            0x0005 => 'Nikon:WhiteBalance',
            0x0007 => 'Nikon:FocusMode',
            0x0008 => 'Nikon:FlashSetting',
            0x0009 => 'Nikon:FlashType',
            0x000D => 'Nikon:ProgramShift',
            0x000E => 'Nikon:ExposureDifference',
            0x0084 => 'Nikon:Lens',
            0x0087 => 'Nikon:FlashMode',
            0x008B => 'Nikon:LensFStops',
            0x0095 => 'Nikon:NoiseReduction',
            0x00A7 => 'Nikon:ShutterCount',
            0x00A9 => 'Nikon:ImageOptimization',
            0x00AA => 'Nikon:Saturation',
            0x00AB => 'Nikon:VariProgram',
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
