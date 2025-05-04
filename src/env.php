<?php

declare(strict_types=1);

namespace Phpgit;

use Error;

final class Env
{
    /** @return array<string,mixed> */
    public static function load(string $filename): array
    {
        $fp = fopen($filename, 'r');
        if ($fp === false) {
            throw new Error(sprintf('failed to load %s', $filename));
        }

        $env = new self();
        $data = [];

        while (($line = fgets($fp)) !== false) {
            if (empty($line)) {
                continue;
            }

            $kvStr = str_replace("\n", '', $line);
            $kv = $env->parseKeyValue($kvStr);
            if (empty($kv)) {
                continue;
            }

            [$key, $value] = $kv;
            $data[$key] = $value;
        }

        return $data;
    }

    /** 
     * @return array{0:string,1:mixed} key, value
     */
    private function parseKeyValue(string $kv): array
    {
        $res = preg_match('/^(.+)?\s*=\s*(.*)/', $kv, $matches);
        if (!$res) {
            return [];
        }

        [$subject, $k, $tmpVal] = $matches;
        $v = match ($tmpVal) {
            'true' => true,
            'false' => false,
            '0' => 0,
            default => match (true) {
                !!preg_match('/^"(.*)"$/', $tmpVal, $double) => $double[1],
                !!preg_match('/^\'(.*)\'$/', $tmpVal, $single) => $single[1],
                !!preg_match('/^\-{0,1}[1-9]\d*$/', $tmpVal) => intval($tmpVal),
                default => $tmpVal,
            },
        };

        return [$k, $v];
    }
}
