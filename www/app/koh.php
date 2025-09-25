<?php

class KohonenSOM {
    private $weights = [];
    private $learningRate;
    private $iterations;
    private $mapSize;
    private $inputSize;

    public function __construct($mapSize, $inputSize, $iterations = 1000, $learningRate = 0.1) {
        $this->mapSize = $mapSize;
        $this->inputSize = $inputSize;
        $this->iterations = $iterations;
        $this->learningRate = $learningRate;

        // Инициализация весов случайными значениями
        for ($i = 0; $i < $mapSize; $i++) {
            for ($j = 0; $j < $inputSize; $j++) {
                $this->weights[$i][$j] = mt_rand() / mt_getrandmax();
            }
        }
    }

    // Евклидово расстояние
    private function distance($vec1, $vec2) {
        $sum = 0.0;
        for ($i = 0; $i < count($vec1); $i++) {
            $sum += pow($vec1[$i] - $vec2[$i], 2);
        }
        return sqrt($sum);
    }

    // Поиск "победителя" (BMU)
    private function findBMU($input) {
        $minDist = INF;
        $bmuIndex = 0;
        for ($i = 0; $i < $this->mapSize; $i++) {
            $dist = $this->distance($input, $this->weights[$i]);
            if ($dist < $minDist) {
                $minDist = $dist;
                $bmuIndex = $i;
            }
        }
        return $bmuIndex;
    }

    // Обучение
    public function train($data) {
        for ($iter = 0; $iter < $this->iterations; $iter++) {
            // Выбираем случайный вектор из данных
            $input = $data[array_rand($data)];

            $bmuIndex = $this->findBMU($input);

            // Обновляем веса
            for ($j = 0; $j < $this->inputSize; $j++) {
                $this->weights[$bmuIndex][$j] += $this->learningRate * ($input[$j] - $this->weights[$bmuIndex][$j]);
            }

            // Постепенно уменьшаем скорость обучения
            $this->learningRate *= 0.999;
        }
    }

    public function getWeights() {
        return $this->weights;
    }

    public function classify($input) {
        return $this->findBMU($input);
    }
}

// ==== Пример использования ====
$data = [
    [0.1, 0.2],
    [0.2, 0.1],
    [0.8, 0.9],
    [0.9, 0.85]
];

$som = new KohonenSOM(mapSize: 5, inputSize: 2, iterations: 500, learningRate: 0.2);
$som->train($data);

echo "Веса нейронов после обучения:\n";
print_r($som->getWeights());

$test = [0.15, 0.1];
echo "Класс для тестового вектора: " . $som->classify($test) . "\n";



 //двумерная
class KohonenSOM2D {
    private $weights = [];
    private $learningRate;
    private $iterations;
    private $width;
    private $height;
    private $inputSize;

    public function __construct($width, $height, $inputSize, $iterations = 1000, $learningRate = 0.1) {
        $this->width = $width;
        $this->height = $height;
        $this->inputSize = $inputSize;
        $this->iterations = $iterations;
        $this->learningRate = $learningRate;

        // Инициализация весов случайными значениями
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                for ($j = 0; $j < $inputSize; $j++) {
                    $this->weights[$x][$y][$j] = mt_rand() / mt_getrandmax();
                }
            }
        }
    }

    private function distance($vec1, $vec2) {
        $sum = 0.0;
        for ($i = 0; $i < count($vec1); $i++) {
            $sum += pow($vec1[$i] - $vec2[$i], 2);
        }
        return sqrt($sum);
    }

    // Евклидово расстояние в сетке между нейронами
    private function gridDistance($x1, $y1, $x2, $y2) {
        return sqrt(pow($x1 - $x2, 2) + pow($y1 - $y2, 2));
    }

    private function findBMU($input) {
        $minDist = INF;
        $bmu = [0, 0];
        for ($x = 0; $x < $this->width; $x++) {
            for ($y = 0; $y < $this->height; $y++) {
                $dist = $this->distance($input, $this->weights[$x][$y]);
                if ($dist < $minDist) {
                    $minDist = $dist;
                    $bmu = [$x, $y];
                }
            }
        }
        return $bmu;
    }

    public function train($data) {
        $initialRadius = max($this->width, $this->height) / 2;
        $timeConstant = $this->iterations / log($initialRadius);

        for ($iter = 0; $iter < $this->iterations; $iter++) {
            $input = $data[array_rand($data)];

            [$bmuX, $bmuY] = $this->findBMU($input);

            // Радиус соседства уменьшается
            $radius = $initialRadius * exp(-$iter / $timeConstant);

            // Скорость обучения уменьшается
            $lr = $this->learningRate * exp(-$iter / $this->iterations);

            for ($x = 0; $x < $this->width; $x++) {
                for ($y = 0; $y < $this->height; $y++) {
                    $distToBMU = $this->gridDistance($x, $y, $bmuX, $bmuY);

                    if ($distToBMU < $radius) {
                        // Функция влияния (гауссиан)
                        $influence = exp(-($distToBMU ** 2) / (2 * $radius ** 2));

                        for ($j = 0; $j < $this->inputSize; $j++) {
                            $this->weights[$x][$y][$j] += 
                                $lr * $influence * ($input[$j] - $this->weights[$x][$y][$j]);
                        }
                    }
                }
            }
        }
    }

    public function getWeights() {
        return $this->weights;
    }

    public function classify($input) {
        return $this->findBMU($input);
    }
}

// ==== Пример использования ====
$data = [
    [0.1, 0.2],
    [0.2, 0.1],
    [0.8, 0.9],
    [0.9, 0.85],
    [0.4, 0.5],
    [0.6, 0.55]
];

$som = new KohonenSOM2D(width: 5, height: 5, inputSize: 2, iterations: 1000, learningRate: 0.2);
$som->train($data);

echo "Веса карты после обучения:\n";
print_r($som->getWeights());

$test = [0.15, 0.1];
[$x, $y] = $som->classify($test);
echo "Тестовый вектор ближе всего к нейрону: ($x, $y)\n";

 
class KohonenSOM2D {
    private $weights = [];
    private $learningRate;
    private $iterations;
    private $width;
    private $height;
    private $inputSize;

    public function __construct($width, $height, $inputSize, $iterations = 1000, $learningRate = 0.1) {
        $this->width = $width;
        $this->height = $height;
        $this->inputSize = $inputSize;
        $this->iterations = $iterations;
        $this->learningRate = $learningRate;

        // Инициализация весов случайными значениями [0..1]
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                for ($j = 0; $j < $inputSize; $j++) {
                    $this->weights[$x][$y][$j] = mt_rand() / mt_getrandmax();
                }
            }
        }
    }

    private function distance($vec1, $vec2) {
        $sum = 0.0;
        for ($i = 0; $i < count($vec1); $i++) {
            $sum += pow($vec1[$i] - $vec2[$i], 2);
        }
        return sqrt($sum);
    }

    private function gridDistance($x1, $y1, $x2, $y2) {
        return sqrt(pow($x1 - $x2, 2) + pow($y1 - $y2, 2));
    }

    private function findBMU($input) {
        $minDist = INF;
        $bmu = [0, 0];
        for ($x = 0; $x < $this->width; $x++) {
            for ($y = 0; $y < $this->height; $y++) {
                $dist = $this->distance($input, $this->weights[$x][$y]);
                if ($dist < $minDist) {
                    $minDist = $dist;
                    $bmu = [$x, $y];
                }
            }
        }
        return $bmu;
    }

    public function train($data) {
        $initialRadius = max($this->width, $this->height) / 2;
        $timeConstant = $this->iterations / log($initialRadius);

        for ($iter = 0; $iter < $this->iterations; $iter++) {
            $input = $data[array_rand($data)];
            [$bmuX, $bmuY] = $this->findBMU($input);

            $radius = $initialRadius * exp(-$iter / $timeConstant);
            $lr = $this->learningRate * exp(-$iter / $this->iterations);

            for ($x = 0; $x < $this->width; $x++) {
                for ($y = 0; $y < $this->height; $y++) {
                    $distToBMU = $this->gridDistance($x, $y, $bmuX, $bmuY);
                    if ($distToBMU < $radius) {
                        $influence = exp(-($distToBMU ** 2) / (2 * $radius ** 2));
                        for ($j = 0; $j < $this->inputSize; $j++) {
                            $this->weights[$x][$y][$j] += 
                                $lr * $influence * ($input[$j] - $this->weights[$x][$y][$j]);
                        }
                    }
                }
            }
        }
    }

    public function getWeights() {
        return $this->weights;
    }
}

// ==== Пример использования ====
// Данные — случайные RGB цвета
$data = [];
for ($i = 0; $i < 100; $i++) {
    $data[] = [mt_rand()/mt_getrandmax(), mt_rand()/mt_getrandmax(), mt_rand()/mt_getrandmax()];
}

// Создаём SOM-карту 20×20, вход 3 (RGB)
$som = new KohonenSOM2D(20, 20, 3, 2000, 0.2);
$som->train($data);
$weights = $som->getWeights();

// ==== Визуализация ====
$cellSize = 20;
$imgWidth = 20 * $cellSize;
$imgHeight = 20 * $cellSize;
$image = imagecreatetruecolor($imgWidth, $imgHeight);

for ($x = 0; $x < 20; $x++) {
    for ($y = 0; $y < 20; $y++) {
        [$r, $g, $b] = $weights[$x][$y];
        $color = imagecolorallocate($image, intval($r * 255), intval($g * 255), intval($b * 255));
        imagefilledrectangle(
            $image,
            $x * $cellSize, $y * $cellSize,
            ($x + 1) * $cellSize, ($y + 1) * $cellSize,
            $color
        );
    }
}

// Сохраняем в файл
header("Content-Type: image/png");
imagepng($image);
imagedestroy($image);

