<?php
  

class NeuralNetwork {
    private $inputSize;
    private $hiddenSize1;
    private $hiddenSize2;
    private $outputSize;
    private $learningRate;

    private $weights1;
    private $weights2;
    private $weights3;

    private $bias1;
    private $bias2;
    private $bias3;

    public function __construct($inputSize = 5, $hiddenSize1 = 4, $hiddenSize2 = 3, $outputSize = 3, $learningRate = 0.1) {
        $this->inputSize = $inputSize;
        $this->hiddenSize1 = $hiddenSize1;
        $this->hiddenSize2 = $hiddenSize2;
        $this->outputSize = $outputSize;
        $this->learningRate = $learningRate;

        $this->weights1 = $this->randomMatrix($inputSize, $hiddenSize1);
        $this->weights2 = $this->randomMatrix($hiddenSize1, $hiddenSize2);
        $this->weights3 = $this->randomMatrix($hiddenSize2, $outputSize);

        $this->bias1 = $this->randomArray($hiddenSize1);
        $this->bias2 = $this->randomArray($hiddenSize2);
        $this->bias3 = $this->randomArray($outputSize);
    }

    private function randomMatrix($rows, $cols) {
        $m = [];
        for ($i = 0; $i < $rows; $i++) {
            for ($j = 0; $j < $cols; $j++) {
                $m[$i][$j] = (mt_rand() / mt_getrandmax()) * 2 - 1;
            }
        }
        return $m;
    }

    private function randomArray($size) {
        $arr = [];
        for ($i = 0; $i < $size; $i++) {
            $arr[$i] = (mt_rand() / mt_getrandmax()) * 2 - 1;
        }
        return $arr;
    }

    private function sigmoid($x) {
        return 1 / (1 + exp(-$x));
    }

    private function sigmoidDerivative($x) {
        return $x * (1 - $x);
    }

    private function softmax($z) {
        $exp = array_map('exp', $z);
        $sum = array_sum($exp);
        return array_map(fn($v) => $v / $sum, $exp);
    }

    private function dot($inputs, $weights, $bias, $activation = "sigmoid") {
        $result = [];
        $cols = count($weights[0]);

        for ($j = 0; $j < $cols; $j++) {
            $sum = $bias[$j];
            for ($i = 0; $i < count($inputs); $i++) {
                $sum += $inputs[$i] * $weights[$i][$j];
            }
            if ($activation === "sigmoid") {
                $result[$j] = $this->sigmoid($sum);
            } else {
                $result[$j] = $sum; // для softmax оставляем "сырые" значения
            }
        }
        if ($activation === "softmax") {
            return $this->softmax($result);
        }
        return $result;
    }

    public function forward($inputs) {
        $hidden1 = $this->dot($inputs, $this->weights1, $this->bias1, "sigmoid");
        $hidden2 = $this->dot($hidden1, $this->weights2, $this->bias2, "sigmoid");
        $output  = $this->dot($hidden2, $this->weights3, $this->bias3, "softmax");
        return [$hidden1, $hidden2, $output];
    }

    public function train($data, $labels, $epochs = 1000) {
        for ($epoch = 0; $epoch < $epochs; $epoch++) {
            $totalError = 0;

            foreach ($data as $index => $inputs) {
                $expected = $labels[$index];

                // ==== Прямой проход ====
                [$hidden1, $hidden2, $output] = $this->forward($inputs);

                // ==== Ошибка (кросс-энтропия) ====
                $outputErrors = [];
                for ($i = 0; $i < $this->outputSize; $i++) {
                    $outputErrors[$i] = $expected[$i] - $output[$i];
                    $totalError += -$expected[$i] * log(max($output[$i], 1e-15)); 
                }

                // ==== Дельты для выхода (softmax + cross-entropy) ====
                $outputDeltas = $outputErrors; // производная упрощается

                // ==== Ошибки 2-го скрытого слоя ====
                $hidden2Errors = [];
                for ($i = 0; $i < $this->hiddenSize2; $i++) {
                    $sum = 0;
                    for ($j = 0; $j < $this->outputSize; $j++) {
                        $sum += $outputDeltas[$j] * $this->weights3[$i][$j];
                    }
                    $hidden2Errors[$i] = $sum;
                }

                $hidden2Deltas = [];
                for ($i = 0; $i < $this->hiddenSize2; $i++) {
                    $hidden2Deltas[$i] = $hidden2Errors[$i] * $this->sigmoidDerivative($hidden2[$i]);
                }

                // ==== Ошибки 1-го скрытого слоя ====
                $hidden1Errors = [];
                for ($i = 0; $i < $this->hiddenSize1; $i++) {
                    $sum = 0;
                    for ($j = 0; $j < $this->hiddenSize2; $j++) {
                        $sum += $hidden2Deltas[$j] * $this->weights2[$i][$j];
                    }
                    $hidden1Errors[$i] = $sum;
                }

                $hidden1Deltas = [];
                for ($i = 0; $i < $this->hiddenSize1; $i++) {
                    $hidden1Deltas[$i] = $hidden1Errors[$i] * $this->sigmoidDerivative($hidden1[$i]);
                }

                // ==== Обновление весов ====
                // 2й скрытый → выход
                for ($i = 0; $i < $this->hiddenSize2; $i++) {
                    for ($j = 0; $j < $this->outputSize; $j++) {
                        $this->weights3[$i][$j] += $this->learningRate * $outputDeltas[$j] * $hidden2[$i];
                    }
                }
                for ($j = 0; $j < $this->outputSize; $j++) {
                    $this->bias3[$j] += $this->learningRate * $outputDeltas[$j];
                }

                // 1й скрытый → 2й скрытый
                for ($i = 0; $i < $this->hiddenSize1; $i++) {
                    for ($j = 0; $j < $this->hiddenSize2; $j++) {
                        $this->weights2[$i][$j] += $this->learningRate * $hidden2Deltas[$j] * $hidden1[$i];
                    }
                }
                for ($j = 0; $j < $this->hiddenSize2; $j++) {
                    $this->bias2[$j] += $this->learningRate * $hidden2Deltas[$j];
                }

                // вход → 1й скрытый
                for ($i = 0; $i < $this->inputSize; $i++) {
                    for ($j = 0; $j < $this->hiddenSize1; $j++) {
                        $this->weights1[$i][$j] += $this->learningRate * $hidden1Deltas[$j] * $inputs[$i];
                    }
                }
                for ($j = 0; $j < $this->hiddenSize1; $j++) {
                    $this->bias1[$j] += $this->learningRate * $hidden1Deltas[$j];
                }
            }

            if ($epoch % 100 == 0) {
                echo "Эпоха $epoch, Ошибка: $totalError\n";
            }
        }
    }

    public function predict($inputs) {
        [, , $output] = $this->forward($inputs);
        return $output;
    }
}

// ==== Пример использования ====
// Входы (5 признаков), выходы (One-Hot для 3 классов)
$data = [
    [1,0,0,0,1],
    [0,1,0,1,0],
    [0,0,1,0,1],
    [1,1,0,0,0],
    [0,0,0,1,1]
];

$labels = [
    [1,0,0], // класс 0
    [0,1,0], // класс 1
    [0,0,1], // класс 2
    [1,0,0], // класс 0
    [0,1,0]  // класс 1
];

$nn = new NeuralNetwork();
$nn->train($data, $labels, 1000);

// Тестирование
echo "Прогнозы:\n";
foreach ($data as $i => $inputs) {
    $out = $nn->predict($inputs);
    echo "Вход: ".json_encode($inputs)." → Выход: ".json_encode($out)." → Класс: ".array_search(max($out), $out)."\n";
}

