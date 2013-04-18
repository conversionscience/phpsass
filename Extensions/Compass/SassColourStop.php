<?php

class SassColourStopException extends SassScriptParserException {}

class SassColourStop extends SassLiteral
{
    public $colour = null;
    public $stop = null;

    public function __construct($token, $stop = null)
    {
        if (is_string($token) || $token instanceof SassString && $token = $token->toString()) {
            $token = trim($token);
            $parts = new SassList($token, ' ');

            if ($parts->length() === 2 &&
                    SassColour::isa($parts->nth(1)) &&
                    SassNumber::isa($parts->nth(2))) {
                $this->colour = $parts->nth(1);
                $this->stop = $parts->nth(2);
            } else if ($parts->length() === 1 &&
                    SassColour::isa($parts->nth(1))) {
                $this->colour = $parts->nth(1);
            } else {
                throw
                    new SassColourStopException("Wrong format for colour stop");
            }
        } else if ($token instanceof SassColour) {
            $this->colour = $token;
        }

        if ($stop && SassNumber::isa($stop)) {
            $this->stop = new SassNumber($stop);
        }
    }

    public function getColour()
    {
        return $this->colour;
    }

    public function getStop()
    {
        return $this->stop;
    }

    public static function isa($subject)
    {
        $regex = preg_replace('/\$?\/$/', '', SassColour::getRegex()) . '\s+' .
            preg_replace('/^\/\^/', '', SassNumber::MATCH);
        return (preg_match($regex, strtolower($subject), $matches) ?
            $matches[0] : false);
    }

    public function toString()
    {
        return $this->colour->asHex() .
            ($this->stop ? ' ' . $this->stop->toString() : '');
    }

}