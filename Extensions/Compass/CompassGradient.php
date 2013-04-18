<?php

abstract class CompassGradient extends SassLiteral implements CompassPrefixesInterface
{
    protected static $prefixes = array('-owg', '-o', '-moz', '-webkit');

    protected $position = null;
    protected $colour_stops = array();

    public function supported($prefix)
    {
        return in_array($prefix, self::$prefixes) !== FALSE;
    }

    public function toString()
    {
        return $this->toPrefix();
    }

    public function getCenterPosition()
    {
        return new SassList(array(
            new SassString('center'),
            new SassString('center')
        ));
    }

    public function toGradPoint($position)
    {

        if (!($position instanceof SassList)) {
            $position = new SassList(array($position), " ");
        } else {
            $position = new SassList($position->value, " ");
        }

        if ($position->length() == 1) {
            if (preg_match('/top|bottom/', $position->nth(1)->toString())) {
                array_unshift($position->value, new SassString('center'));
            } else if (preg_match('/left|right/', $position->nth(1)->toString())) {
                $position->value[] = new SassString('center');
            }
        }

        if (preg_match('/top|bottom/', $position->value[0]) ||
                preg_match('/left|right/', $position->nth(2))) {
            $position->value = array_reverse($position->value);
        }

        $position->value = array_map(function($e) {
            $val = $e->toString();
            if (preg_match('/left|top/', $val)) {
                return new SassNumber('0');
            } else if (preg_match('/bottom|right/', $val)) {
                return new SassNumber('100%');
            } else if (preg_match('/center/', $val)) {
                return new SassNumber('50%');
            } else {
                return $e;
            }
        }, $position->value);
        return $position;
    }

    public function toGradColourStops($positions)
    {
        $stops = array_map(function($stop) {
            list($stop, $colour) = $stop;
            return sprintf("color-stop(%s, %s)", $stop->toString(), $colour->toString());
        }, $this->toColourStopsInPercentages($positions));
        return new SassString(join(', ', $stops));
    }

    public function toColourStopsInPercentages($positions)
    {
        $positions = $this->normalizeStops($positions);
        $max = $positions->nth($positions->length())->stop;
        $last = null;

        return array_map(function($pos) use($max, $last) {
            $stop = $pos->stop;
            if ($stop->numeratorUnits == $max->numeratorUnits &&
                    $max->numeratorUnits != array("%")) {
                $stop = $stop->op_div($max)->op_times(new SassNumber('100%'));
            }

            if (!is_null($last) && $stop->numeratorUnits == $last->numeratorUnits &&
                $stop->denominatorUnits == $last->denominatorUnits &&
                round($stop->value * 1000) < round($last->value * 1000)) {
                throw new SassException("Color stops must be specified in increasing order. $stop->value came after $last->value");
            }

            $last = $stop;

            return array($stop, $pos->colour);
        }, $positions->value);
    }

    public function normalizeStops($positions)
    {
        $positions = array_map(function($c) {
            return new SassColourStop($c->colour, $c->stop);
        }, $positions);

        $pos = reset($positions);
        if (is_null($pos->stop)) {
            $pos->stop = new SassNumber('0');
        }

        $pos = end($positions);
        if (is_null($pos->stop)) {
            $pos->stop = new SassNumber('100%');
        }

        for ($i = 0; $i < sizeof($positions); $i++) {
            if (!$positions[$i]->stop) {
                $num = 2;
                for ($j = $i + 1; $j < sizeof($positions); $j++) {
                    if ($positions[$j]->stop) {
                        $positions[$j]->stop = $positions[$i - 1]->stop->plus($positions[$j]->stop->minus($positions[$i - 1]->stop)->op_div(new SassNumber(num)));
                        break;
                    } else {
                        $num++;
                    }
                }
            }
        }

        foreach ($positions as $pos) {
            if ($pos->stop->isUnitless() && $pos->stop->value <= 1) {
                $pos->stop->op_times(new SassNumber("100%"));
            } else if ($pos->stop->isUnitless()) {
                $pos->stop->op_times(new SassNumber("1px"));
            }
        }

        return new SassList($positions);
    }



    public abstract function toPrefix($prefix = '');
}
