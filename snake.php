<?php

class Snake {

    protected $width;
    protected $height;
    protected $buffer = array();

    protected $objects = array();
    protected $snake = array();

    protected $direction = false;

    protected $dead = false;

    protected $score = 0;
    protected $level = 1;
    protected $numnumnum = 0;

    function __construct($width, $height) {
        $this->width = $width;
        $this->height = $height;

        $this->background = array();
        for ($y = 0; $y != $this->height; $y++) {
            $this->buffer[$y] = array();
        }

        $this->snake = array(
            $this->getRandomPoint(),
        );

        $this->objects = array(
            array('type' => 'food', 'point' => $this->getFreePoint()),
            array('type' => 'bomb', 'point' => $this->getFreePoint()),
            array('type' => 'hole', 'point' => $this->getFreePoint()),
        );

        $this->stdin = fopen("php://stdin", "r");
        system("stty -icanon -echo");
    }

    function plotBuffer($x, $y, $fg, $bg, $ch) {
        $this->buffer[$x][$y] = array($fg, $bg, $ch);
    }

    function play() {
        while (! $this->dead) {
            $this->step();
            $this->render();
        }
    }

    function step()
    {
        $r = array($this->stdin); $w = null; $e = null;
        stream_select($r, $w, $e, 0, 50 * 1000);
        if ($r) {
            $this->processKeyboard();
        }

        $this->moveSnake();
    }

    function processKeyboard() {
        $escape_sequence = false;
        $c = ord(fgetc($this->stdin));
        if ($c == 27) {
            $c = ord(fgetc($this->stdin));
            $c = ord(fgetc($this->stdin));
            $escape_sequence = true;
        }

        if ($escape_sequence) {
            switch ($c) {
                case 65 : $this->direction = $this->direction === 180 ? $this->direction :   0; break;  // Up       0
                case 66 : $this->direction = $this->direction ===   0 ? $this->direction : 180; break;  // Down   180
                case 68 : $this->direction = $this->direction ===  90 ? $this->direction : 270; break;  // Left   270
                case 67 : $this->direction = $this->direction === 270 ? $this->direction :  90; break;  // Right   90
            }
        }
    }

    function moveSnake() {
        // Not moving yet.
        if ($this->direction === false) {
            return;
        }

        $new_head = $this->snake[0];
        switch ($this->direction) {
            case 0:
                $new_head[1]--;
                break;
            case 90:
                $new_head[0]++;
                break;
            case 180:
                $new_head[1]++;
                break;
            case 270:
                $new_head[0]--;
                break;
        }

        if ($new_head[0] < 0 || $new_head[1] < 0 ||
            $new_head[0] > $this->width-1 || $new_head[1] > $this->height-1) {
            $this->direction = false;

            $this->score -= ($this->level * 100);
            if ($this->score < 0) {
                $this->score = 0;
            }
        }

        if ($this->direction === false) {
            return;
        }


        $c = $this->collidesWithObject($new_head, $this->objects);
        if ($c !== false) {
            if ($this->objects[$c]['type'] == 'food') {
                $this->objects[$c] = array('type' => 'food', 'point' => $this->getFreePoint());
                $this->score += (75 * $this->level);
                $this->numnumnum++;
                if ($this->numnumnum % 5 == 0) {
                    $this->level++;
                }

                array_unshift($this->snake, $new_head);
            } elseif ($this->objects[$c]['type'] == 'bomb') {
                $this->dead = true;
            }
        } else {
            array_unshift($this->snake, $new_head);
            array_pop($this->snake);
        }


        $this->score += $this->level;
    }

    function getFreePoint() {
        do {
            $point = $this->getRandomPoint();
        } while ($this->collides($point, $this->snake) !== false);
        
        return $point;
    }

    function collidesWithObject($point, $objects) {
        foreach ($objects as $k => $object) {
            if ($this->collides($point, array($object['point'])) !== false) {
                return $k;
            }
        }

        return false;
    }

    function collides($point, $points) {
        foreach ($points as $k => $p) {
            if ($p[0] == $point[0] && $p[1] == $point[1]) {
                return $k;
            }
        }
        return false;
    }

    function renderSnake() {
        foreach ($this->snake as $point) {
            $this->plotBuffer($point[0], $point[1], 37, 41, "\xE2\x96\x88");
        }
    }

    function renderObjects() {
        foreach ($this->objects as $object) {
            switch ($object['type']) {
                case 'food' :
                    $this->plotBuffer($object['point'][0], $object['point'][1], 33, 40, "\xF0\x9F\x8D\xBA");
                    break;
                case 'bomb' :
                    $this->plotBuffer($object['point'][0], $object['point'][1], 33, 40, "\xF0\x9F\x92\xA3");
                    break;
                case 'hole' :
                    $this->plotBuffer($object['point'][0], $object['point'][1], 48, 46, "O");
                    break;
            }
        }
    }

    function renderBackground() {
        for ($y = 0; $y != $this->height; $y++) {
            for ($x = 0; $x != $this->width; $x++) {
                $this->plotBuffer($x, $y, 34, 40, '.');
            }
        }
    }

    function render()
    {
        $this->renderBackground();
        $this->renderObjects();
        $this->renderSnake();

        echo "\033[J";
        echo "\033[H";

        echo "\033[36;1m";
        print "\xE2\x94\x8F".str_repeat("\xE2\x94\x81", $this->width)."\xE2\x94\x93     \n";

        for ($y = 0; $y != $this->height; $y++) {
            echo "\033[36;1m";
            print "\xE2\x94\x83";

            $c_fg = -1;
            $c_bg = -1;
            for ($x = 0; $x != $this->width; $x++) {
                list($fg, $bg, $ch) = $this->buffer[$x][$y];

                if ($fg != $c_fg || $bg != $c_bg) {
                    echo "\033[".$fg."m";
                    echo "\033[".$bg."m";
                    $c_fg = $fg;
                    $c_bg = $bg;
                }
                echo $ch;
            }

            echo "\033[36;1m";
            print "\xE2\x94\x83";
            echo "   \n";
        }

        echo "\033[36;1m";
        print "\xE2\x94\x97".str_repeat("\xE2\x94\x81", $this->width)."\xE2\x94\x9B\n";

        echo "\033[37;1m";
        print "                            <<< SNAKES ON A PLAIN >>>\n";
        printf("                   Level %02d   Score: %06d    Food: %03d", $this->level, $this->score, $this->numnumnum);

        echo "\033[39;49m";
        echo "\033[0m";
    }

    function getRandomPoint() {

        $x = mt_rand(1, $this->width-2);
        $y = mt_rand(1, $this->height-2);

        return array($x, $y);
    }

}

$snake = new Snake(80, 25);
$snake->play();
