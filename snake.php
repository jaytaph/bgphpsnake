<?php

class Snake {

    protected $width;
    protected $height;
    protected $renderBuffers = array();

    protected $objects = array();
    protected $snake = array();

    protected $direction = false;

    protected $dead = false;

    protected $score = 0;
    protected $level = 1;
    protected $numnumnum = 0;

    protected $curPortal = 0;

    const MAX_PLANE = 3;

    function __construct($width, $height) {
        $this->width = $width;
        $this->height = $height;

        $this->background = array();
        for ($z = 0; $z != self::MAX_PLANE; $z++) {
            $this->renderBuffers[$z] = array();
            for ($y = 0; $y != $this->height; $y++) {
                $this->renderBuffers[$z][$y] = array();
            }
        }

        $this->snake = array(
            $this->getRandomPoint($this->curPortal),
        );

        $this->objects = array(
            array('type' => 'food', 'point' => $this->getFreePoint()),
            array('type' => 'bomb', 'point' => $this->getFreePoint()),
            array('type' => 'bomb', 'point' => $this->getFreePoint()),
            array('type' => 'bomb', 'point' => $this->getFreePoint()),
            array('type' => 'bomb', 'point' => $this->getFreePoint()),
            array('type' => 'bomb', 'point' => $this->getFreePoint()),
            array('type' => 'bomb', 'point' => $this->getFreePoint()),
            array('type' => 'bomb', 'point' => $this->getFreePoint()),
            array('type' => 'bomb', 'point' => $this->getFreePoint()),
            array('type' => 'bomb', 'point' => $this->getFreePoint()),
            array('type' => 'bomb', 'point' => $this->getFreePoint()),
            array('type' => 'bomb', 'point' => $this->getFreePoint()),
        );

        $this->generatePortal(0, 1);
        $this->generatePortal(0, 2);
        $this->generatePortal(1, 2);

        $this->stdin = fopen("php://stdin", "r");
        system("stty -icanon -echo");
    }

    function generatePortal($src, $dst) {
        $h1 = $this->getFreePoint($src);
        $h2 = $this->getFreePoint($dst);

        $this->objects[] = array('type' => 'portal', 'point' => $h1, 'dst_point' => $h2);
        $this->objects[] = array('type' => 'portal', 'point' => $h2, 'dst_point' => $h1);
    }

    function plotBuffer($x, $y, $z, $fg, $bg, $ch) {
        $this->renderBuffers[$z][$y][$x] = array($fg, $bg, $ch);
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
        stream_select($r, $w, $e, 0, 25 * 1000);
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
            } elseif ($this->objects[$c]['type'] == 'portal') {
                // Lets move through a portal
                $new_head = $this->objects[$c]['dst_point'];
                $this->curPlane = $new_head[2];

                array_unshift($this->snake, $new_head);
            }
        } else {
            array_unshift($this->snake, $new_head);
            array_pop($this->snake);
        }


        $this->score += $this->level;
    }

    function getFreePoint($plane = null) {
        do {
            $point = $this->getRandomPoint($plane);
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
            if ($p[0] == $point[0] && $p[1] == $point[1] && $p[2] == $point[2]) {
                return $k;
            }
        }
        return false;
    }

    function getRandomPoint($plane) {
        $x = mt_rand(1, $this->width-2);
        $y = mt_rand(1, $this->height-2);
        if ($plane === null) {
            $z = mt_rand(0, self::MAX_PLANE-1);
        } else {
            $z = $plane;
        }

        return array($x, $y, $z);
    }

    function renderSnake() {
        foreach ($this->snake as $point) {
            $this->plotBuffer($point[0], $point[1], $point[2], 37, 41, "\xE2\x96\x88");
        }
    }

    function renderObjects() {
        foreach ($this->objects as $object) {
            switch ($object['type']) {
                case 'food' :
                    $this->plotBuffer($object['point'][0], $object['point'][1], $object['point'][2], 33, 40, "\xF0\x9F\x8D\xBA");
                    break;
                case 'bomb' :
                    $this->plotBuffer($object['point'][0], $object['point'][1], $object['point'][2], 33, 40, "\xF0\x9F\x92\xA3");
                    break;
                case 'portal' :
                    $this->plotBuffer($object['point'][0], $object['point'][1], $object['point'][2], 48, 46, "O");
                    break;
            }
        }
    }

    function renderBackground() {
        for ($z = 0; $z != self::MAX_PLANE; $z++) {
            for ($y = 0; $y != $this->height; $y++) {
                for ($x = 0; $x != $this->width; $x++) {
                    $this->plotBuffer($x, $y, $z, 34, 40, '.');
                }
            }
        }
    }

    function render()
    {
        $this->renderBackground();
        $this->renderObjects();
        $this->renderSnake();

        $s = "";

        $s .= "\033[36;1m";
        for ($plane=0; $plane!=self::MAX_PLANE; $plane++) {
            $s .= "+". str_repeat("-", $this->width)."+   ";
        }
        $s .= "\n";

        for ($y = 0; $y != $this->height; $y++) {

            for ($z=0; $z!=self::MAX_PLANE; $z++) {
                $s .= "\033[36;1m|";

                $c_fg = -1;
                $c_bg = -1;
                for ($x = 0; $x != $this->width; $x++) {
                    list($fg, $bg, $ch) = $this->renderBuffers[$z][$y][$x];

                    if ($fg != $c_fg || $bg != $c_bg) {
                        $s .= "\033[" . $fg . "m";
                        $s .= "\033[" . $bg . "m";
                        $c_fg = $fg;
                        $c_bg = $bg;
                    }
                    $s .= $ch;
                }

                $s .= "\033[36;40;1m|   ";
            }
            $s .= "\n";
        }

        $s .= "\033[36;1m";
        for ($plane=0; $plane!=self::MAX_PLANE; $plane++) {
            $s .= "+". str_repeat("-", $this->width)."+   ";
        }
        $s .= "\n";

        $s .= "\033[37;1m";
        $s .= "                                                                                      ";
        $s .= sprintf("                   Level %02d   Score: %06d    Food: %03d", $this->level, $this->score, $this->numnumnum);

        $s .= "\n\n\n";

        $s .= "\033[35;1m";


$airplane = <<< SAMUELLJACKSON
                      _________________________          _____
                     |                         \          \ U \__      _____
                     |   Snake(s) on a plane    \__________\   \/_______\___\_____________
                     |   BGPHP16 Hackathon      /          < /_/   .....................  `-.
                     |_________________________/            `-----------,----,--------------'
                                                                      _/____/
SAMUELLJACKSON;

//        $this->ctr++;
//        foreach (explode("\n", $airplane) as $line) {
//            $s .= str_repeat(" ", $this->ctr) . $line . "\n";
//        }
        $s .= $airplane;

        $s .= "\n\n\n";

        $s .= "\033[39;49m";
        $s .= "\033[0m";



        print "\033[J";
        print "\033[H";
        print $s;
    }

}

$snake = new Snake(80, 25);
$snake->play();
