<?php

declare(strict_types=1);

namespace Horde\Image\Metadata\Parser;

final class FujifilmParser implements MakerNoteParser
{
    public function supports(string $makeLower): bool
    {
        return str_contains($makeLower, 'fuji');
    }

    public function parse(string $data, bool $intel): array
    {
        $result = [];
        $length = strlen($data);

        if ($length < 12 || !str_starts_with($data, "FUJIFILM")) {
            return $result;
        }

        $headerOffset = 12;
        $intel = true;

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
            0x0000 => 'Fuji:Version',
            0x1000 => 'Fuji:Quality',
            0x1001 => 'Fuji:Sharpness',
            0x1002 => 'Fuji:WhiteBalance',
            0x1003 => 'Fuji:Color',
            0x1004 => 'Fuji:Tone',
            0x1010 => 'Fuji:FlashMode',
            0x1011 => 'Fuji:FlashStrength',
            0x1020 => 'Fuji:Macro',
            0x1021 => 'Fuji:FocusMode',
            0x1030 => 'Fuji:SlowSync',
            0x1031 => 'Fuji:PictureMode',
            0x1100 => 'Fuji:ContCine',
            0x1300 => 'Fuji:BlurWarning',
            0x1301 => 'Fuji:FocusWarning',
            0x1302 => 'Fuji:AEWarning',
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
