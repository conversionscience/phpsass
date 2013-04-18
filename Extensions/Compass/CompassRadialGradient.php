<?php

class CompassRadialGradient extends CompassGradient
{

    protected $shape_and_size = null;

    public function __construct()
    {
        $this->colour_stops = func_get_args();
        $this->position = array_shift($this->colour_stops);
        $this->shape_and_size = array_shift($this->colour_stops);

        foreach (array("position", "shape_and_size") as $key) {
            if ($this->{$key} instanceof SassColour ||
                    SassColourStop::isa($this->{$key}->toString())) {
                array_unshift($this->colour_stops, $this->{$key});
                $this->{$key} = null;
            }
        }
        
        if (sizeof($this->colour_stops) < 2) {
            throw new SassException("At least two colour stops are required for a radial-gradient");
        }

        $this->colour_stops = array_map(function($c) {
            return new SassColourStop($c);
        }, $this->colour_stops);
    }

    public function toPrefix($prefix = '')
    {
        $ret = "radial-gradient(";
        if ($this->position) {
            $ret .= $this->position->toString() . ', ';
        }

        if ($this->shape_and_size) {
            $ret .= $this->shape_and_size->toString() . ', ';
        }

        $ret .= join(", ", array_map(function($colour) {
            return $colour->toString();
        }, $this->colour_stops));

        $ret .= ')';

        switch ($prefix) {
            case '-webkit':
            case '-moz':
            case '-o':
                $ret = $prefix . '-' . $ret;
                break;
            case '-owg':
                $pos = $this->position ?: $this->centerPosition();
                $args = array(
                    $this->toGradPoint($pos),
                    new SassString("0"),
                    $this->toGradPoint($pos),
                    $this->toGradEndPosition($this->colour_stops, new SassBoolean("true")),
                    $this->toGradColourStops($this->colour_stops)
                );
                $ret = sprintf("-webkit-gradient(radial, %s)", join(', ', $args));
                break;
        }

        return $ret;
    }

    protected function centerPosition()
    {
        return new SassList(array(new SassString("center"), new SassString("center")), " ");
    }

    public function toGradEndPosition($colour_list, $radial = false)
    {
        if (is_bool($radial)) {
            $radial = new SassBoolean($radial);
        }

        $default = new SassNumber(100);
        return $this->toGradPosition($colour_list, sizeof($colour_list), $default, $radial);
    }

    public function toGradPosition($colour_list, $index, $default, $radial = false)
    {
        if (is_bool($radial)) {
            $radial = new SassBoolean($radial);
        }

        $stop = $colour_list[$index - 1]->stop;

        if ($stop && $radial->value) {
            $orig_stop = $stop;
            if ($stop->isUnitless()) {
                if ($stop->value <= 1) {
                    $stop = $stop->op_times(new SassNumber('100%'));
                } else {
                    $stop = $stop->op_times(new SassNumber('1px'));
                }
            }
            if ($stop->numeratorUnits == array('%') &&
                    $colour_list->nth($colour_list->length()) &&
                    $colour_list->nth($colour_list->length()).numeratorUnits() == array('px')) {
                $stop = $stop->op_times($colour_list->nth($colour_list->length()).op_div(new SassNumber('100%')));
            }
            $ret = $stop->op_div(new SassNumber(1, $stop->numeratorUnits, $stop->denominatorUnits));
        } else if ($stop) {
            $ret = $stop;
        } else {
            $ret = $default;
        }

        return $ret;
    }

}