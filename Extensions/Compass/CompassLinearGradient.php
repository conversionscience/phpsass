<?php

class CompassLinearGradient extends CompassGradient
{
    public function __construct()
    {
        $this->colour_stops = func_get_args();
        $this->position = array_shift($this->colour_stops);

        if ($this->position instanceof SassColour ||
                SassColourStop::isa($this->position->toString())) {
            array_unshift($this->colour_stops, $this->position);
            $this->position = null;
        }
        
        if (sizeof($this->colour_stops) < 2) {
            throw new SassException("At least two colour stops are required for a linear-gradient");
        }

        $this->colour_stops = array_map(function($c) {
            return new SassColourStop($c);
        }, $this->colour_stops);
    }


    public function toPrefix($prefix = '')
    {
        $ret = "linear-gradient(";
        if ($this->position) {
            $ret .= $this->position->toString() . ', ';
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
                $pos = $this->position ?: new SassString('top');
                $ret = array('-webkit-gradient(linear');
                $ret[] = $this->toGradPoint($pos);
                $ret[] = $this->toLinearEndPosition($pos, $this->colour_stops);
                $ret[] = $this->toGradColourStops($this->colour_stops);
                $ret = new SassString(join(', ', $ret) . ')');
                break;
        }

        return $ret;
    }

    protected function toLinearEndPosition($position_or_angle, $colour_list)
    {
        $pos = $this->position ?: new SassString('top');
        $start_point = $this->toGradPoint($pos);
        $end_point = $this->toGradPoint(new SassString(Compass::compassOppositePosition($pos)));
        $end_target = end($colour_list)->stop;

        if ($end_target && $end_target->numeratorUnits == array("px")) {
            if ($start_point->nth(1) == $end_point->nth(1) &&
                    $start_point->nth($start_point->length()) == 0) {
                $end_point->value[1] = new SassNumber($end_target->value);
            } else if ($start_point->nth($start_point->length()) ==
                    $end_point->nth($end_point->length()) &&
                    $start_point->value[0] == 0) {
                $end_point->value[0] = new SassNumber($end_target->value);
            }
        }

        return $end_point;
    }

}