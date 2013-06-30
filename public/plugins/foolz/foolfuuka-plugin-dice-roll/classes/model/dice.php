<?php

namespace Foolz\Foolfuuka\Plugins\DiceRoll\Model;

class Dice
{

    public static function roll($result)
    {
        $data = $result->getObject();

        if (!$data->radix->getValue('plugin_dice_roll_enable')) {
            return null;
        }

        if ($data->email !== false || $data->email != '') {
            if (preg_match('/dice[ +](\d+)[ d+](\d+)(([ +-]+?)(-?\d+))?/', $data->email, $result)) {
                $modifier = '';

                $dice = [
                    'total' => $result[1],
                    'side' => $result[2],
                    'modifier' => (isset($result[3]) ? $result[3] : null),
                    'expr' => (isset($result[4]) ? $result[4] : '+'),
                    'val' => (isset($result[5]) ? $result[5] : 0),
                    'sum' => 0,
                    'output' => []
                ];

                if ($dice['total'] > 25) {
                    return null;
                }

                for ($d = 0; $d < $dice['total']; $d++) {
                    $rand = mt_rand(1, $dice['side']);
                    $dice['sum']  += $rand;
                    $dice['num'][] = $rand;
                }

                if ($dice['modifier'] !== null) {
                    if (strpos($dice['expr'], '-') !== false) {
                        $dice['val'] *= -1;
                    }

                    $dice['sum'] += $dice['val'];
                    $modifier = ($dice['val'] >= 0 ? ' + ' : ' - ') . abs($dice['val']);
                }

                $output = '[b]rolled ' . implode(', ', $dice['num']) . $modifier . ' = ' . $dice['sum'] . '[/b]';
                $data->comment = trim($output . "\n\n" . $data->comment);
            }
        }

        return null;
    }
}
