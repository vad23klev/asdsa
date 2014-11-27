<?php
// This file is part of Correct Writing question type - https://code.google.com/p/oasychev-moodle-plugins/
//
// Correct Writing question type is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Correct Writing is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains hint definitions, that is used by Correct Writing question type.
 *
 * @package    qtype_correctwriting
 * @subpackage hints
 * @copyright  2012 Oleg Sychev, Volgograd State Technical University
 * @author     Oleg Sychev <oasychev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/question/type/poasquestion/poasquestion_string.php');
require_once($CFG->dirroot . '/question/type/poasquestion/hints.php');
require_once($CFG->dirroot . '/question/type/correctwriting/question.php');
require_once($CFG->dirroot . '/blocks/formal_langs/block_formal_langs.php');


/**
 * "What is" hint shows a token value instead of token description
 *
 * @copyright  2013 Sychev Oleg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_correctwriting_hintwhatis extends qtype_specific_hint {

    /** @var mistake, with which this hint is associated. */
    //commit 2
    protected $mistakes; //commit 5
    /** @var token(s) descriptions for the hint. */
    protected $tokendescr;

    public function hint_type() {
        return qtype_specific_hint::CHOOSEN_MULTIPLE_INSTANCE_HINT;
    }

    /**
     * Constructs hint object, remember question to use.
     */
    public function __construct($question, $hintkey, $mistake) {
        $this->question = $question;
        $this->hintkey = $hintkey;
        $this->mistakes = $mistake; //commit 11
        if ($mistake !== null) {
            $this->tokendescr = $this->mistake->token_descriptions();
        }
    }

    public function hint_description() {
        return get_string('whatis', 'qtype_correctwriting', $this->tokendescr);
    }

    // "What is" hint is obviously response based, since it used to better understand mistake message.
    public function hint_response_based() {
        return true;
    }

    /**
     * The hint is disabled when penalty is set above 1.
     * Mistake === null if attempt to create hint was unsuccessfull.
     * Tokendescr === null if there are no token with description on this mistake.
     */
    public function hint_available($response = null) {
        return $this->penalty_for_specific_hint($response) <= 1.0 && $this->mistake !== null && $this->tokendescr !== null;
    }

    public function penalty_for_specific_hint($response = null) {
        $penalty = $this->question->whatishintpenalty;
        if (is_a($this->mistakes, 'qtype_correctwriting_lexeme_absent_mistake')) { //commit 11
            $penalty *= $this->question->absenthintpenaltyfactor;
        }
        return $penalty;
    }

    // Buttons are rendered by the question to place them in specific feedback near relevant mistake message.
    public function button_rendered_by_question() {
        return true;
    }

    public function render_hint($renderer, question_attempt $qa, question_display_options $options, $response = null) {
        if ($this->mistakes !== null) {//commit 11
            $hinttext = new qtype_poasquestion_string($this->mistakes->token_descriptions(true)); //commit 11
            // Capitalize first letter.
            $hinttext[0] = textlib::strtoupper($hinttext[0]);
            return $hinttext;
        }
    }
}

/**
 * "Where place" text hint shows how a token should be placed.
 *
 * @copyright  2013 Sychev Oleg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_correctwriting_hintwheretxt extends qtype_specific_hint {

    /**
     * @var qtype_correctwriting_response_mistake, with which this hint is associated.
     */
    protected $mistake1;//commit 4
    /** @var token(s) descriptions for the hint or value if no description available. */
    protected $tokens = ['']; //comment 7

    public function hint_type() {
        return qtype_specific_hint::CHOOSEN_MULTIPLE_INSTANCE_HINT;
    }

    /**
     * Constructs hint object, remember question to use.
     * @var qtype_correctwriting_response_mistake $mistake.
     */
    public function __construct($question, $hintkey, $mistake) {
        $this->question = $question;
        $this->hintkey = $hintkey;
        $this->mistake = $mistake;
        if ($mistake !== null) {
            $this->token = $this->mistake->token_description($this->mistake->answermistaken[0]);
        }
    }

    public function hint_description() {
        return get_string('wheretxthint', 'qtype_correctwriting', $this->token);
    }

    // "Where" hint is obviously response based, since it used to better understand mistake message.
    public function hint_response_based() {
        return true;
    }

    /**
     * The hint is disabled when penalty is set above 1.
     * Mistake === null if attempt to create hint was unsuccessfull.
     */
    public function hint_available($response = null) {
        return $this->question->wheretxthintpenalty <= 1.0 && $this->mistake !== null;
    }

    public function penalty_for_specific_hint($response = null) {
        return $this->question->wheretxthintpenalty;
    }

    // Buttons are rendered by the question to place them in specific feedback near relevant mistake message.
    public function button_rendered_by_question() {
        return true;
    }

    public function render_hint($renderer, question_attempt $qa, question_display_options $options, $response = null) {
        $hinttext = '';
        if ($this->mistake !== null) {
            $tokenindex = $this->mistake->answermistaken[0];
            $a = new stdClass;
            $a->token = $this->token;
            if ($tokenindex == 0) {// First token.
                $a->before = $this->mistake->token_description(1);
                $hinttext = get_string('wheretxtbefore', 'qtype_correctwriting', $a);
            } else if ($tokenindex == count($this->mistake->stringpair->correctstring()->stream->tokens) - 1) {// Last token.
                $a->after = $this->mistake->token_description($tokenindex - 1);
                $hinttext = get_string('wheretxtafter', 'qtype_correctwriting', $a);
            } else {// Middle token.
                $a->after = $this->mistake->token_description($tokenindex - 1);
                $a->before = $this->mistake->token_description($tokenindex + 1);
                $hinttext = get_string('wheretxtbetween', 'qtype_correctwriting', $a);
            }
            // Capitalize first letter.
            $hinttext = textlib::strtoupper(textlib::substr($hinttext, 0, 1))
                      . textlib::substr($hinttext, 1);
        }
        return $hinttext;
    }
}


/**
 * "Where picture" text hint shows how a token should be placed.
 *
 * @copyright  2013 Sychev Oleg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_correctwriting_hintwherepic extends qtype_specific_hint {

    /**
     * @var qtype_correctwriting_sequence_mistake
     */
    protected $mistake;
    /** @var token(s) descriptions for the hint or value if no description available */
    protected $token = '';

    public function hint_type() {
        return qtype_specific_hint::CHOOSEN_MULTIPLE_INSTANCE_HINT;
    }

    /**
     * Constructs hint object, remember question to use.
     */
    public function __construct($question, $hintkey, $mistake) {
        $this->question = $question;
        $this->hintkey = $hintkey;
        $this->mistake = $mistake;
        if ($mistake !== null) {
            $this->token = $this->mistake->token_description($this->mistake->answermistaken[0]);
        }
    }

    public function hint_description() {
        return get_string('wherepichint', 'qtype_correctwriting', $this->token);
    }

    // "Where" hint is obviously response based, since it used to better understand mistake message.
    public function hint_response_based() {
        return true;
    }

    /**
     * The hint is disabled when penalty is set above 1.
     * Mistake === null if attempt to create hint was unsuccessfull.
     */
    public function hint_available($response = null) {
        return $this->question->wherepichintpenalty <= 1.0 && $this->mistake !== null;
    }

    public function penalty_for_specific_hint($response = null) {
        return $this->question->wherepichintpenalty;
    }

    // Buttons are rendered by the question to place them in specific feedback near relevant mistake message.
    public function button_rendered_by_question() {
        return true;
    }

    public function render_hint($renderer, question_attempt $qa, question_display_options $options, $response = null) {
        global $CFG;
        /* @var qtype_correctwriting_sequence_mistake $selmistake */
        $selmistake = $this->mistake;
        $absent = 'absent_';
        if (textlib::substr($selmistake->mistake_key(), 0, textlib::strlen($absent)) == $absent) {
            $imagedata = $this->prepare_image_data_for_absent_mistake();
        } else {
            $imagedata = $this->prepare_image_data_for_moved_mistake();
        }

        $url  = $CFG->wwwroot . '/question/type/correctwriting/wherepicimage.php?data=' . urlencode($imagedata);
        $imagesrc = html_writer::empty_tag('image', array('src' => $url));
        return $imagesrc;
    }

    /**
     * Converts token to string
     * @param  block_formal_langs_token_base  $p
     * @return string
     */
    protected static function to_string($p) {
        return $p->value();
    }

    /**
     * Creates response text for image
     * @return string
     */
    protected function create_response_text_for_image() {
        $stream = $this->mistake->stringpair->correctedstring()->stream;
        $temp  = array_map('qtype_correctwriting_hintwherepic::to_string', $stream->tokens);
        $temp = array_map('base64_encode', $temp);
        return implode('|', $temp);
    }
    /**
     * Prepares image data for added mistake.
     */
    protected function prepare_image_data_for_absent_mistake() {
        $result = array();
        $result[]  = 'sugar'; // commit 9
        // TODO - check whether we really need to have token value not quoted (adding ", false" to token_description call) there.
        $result[] = base64_encode($this->mistake->token_description($this->mistake->answermistaken[0]));
        $pos =  $this->find_insertion_position_for($this->mistake->answermistaken[0]);
        $result[] = $pos->position;
        $result[] = $pos->relative;
        $result[] = $this->create_response_text_for_image();

        return base64_encode(implode(',,,', $result));
    }
    /**
     * Prepares image data for moved mistake.
     */
    protected function prepare_image_data_for_moved_mistake() {
        $result = array();
        $result[]  = 'moved';
        $result[] = $this->mistake->responsemistaken[0];
        $pos =  $this->find_insertion_position_for($this->mistake->answermistaken[0]);
        $result[] = $pos->position;
        $result[] = $pos->relative;
        $result[] = $this->create_response_text_for_image();

        return base64_encode(implode(',,,', $result));
    }
    /**
     * Finds a nearest position to known $position of answer in response LCS, searching in direction.
     * @param int $position  a position.
     * @param int $direction sequence length.
     * @param int $dist distance from requested position, where match was found.
     * @return null|int position.
     */
    private function find_response_position($position, $direction, &$dist) {
        /* @var qtype_correctwriting_sequence_mistake $selmistake */
        $selmistake = $this->mistake;
        $lcs = $selmistake->lcs();
        $dist = 1;
        $curposition = $position + $direction;
        $found = null;
        $answerstream = $selmistake->stringpair->correctstring()->stream;
        while (($curposition > -1) && ($curposition < count($answerstream->tokens)) && ($found === null)) {
            if (array_key_exists($curposition, $lcs)) {
                $found = $lcs[$curposition];
            } else {
                $curposition += $direction;
                $dist = $dist + 1;
            }
        }
        return $found;
    }
    /**
     * Finds insertion position for current mistake in response.
     * @param int $position position of token in answer string.
     * @return stdClass  <int position, string  relative before|after> int position of token in response string, relative.
     * determines where token should be placed, before or after selected.
     */
    private function find_insertion_position_for($position) {
        /* @var qtype_correctwriting_sequence_mistake $selmistake */
        $selmistake = $this->mistake;
        $result = new stdClass();
        $lcs = $selmistake->lcs();
        if (count($lcs) == 0) {
            $result->position = 0;
            $result->relative = 'before';
        } else {
            $distprevious = 0;
            $distnext = 0;
            $posprevious = $this->find_response_position($position, -1, $distprevious);
            $posnext = $this->find_response_position($position, 1, $distnext);
            if ($posprevious === null) {
                if ($posnext === null) {
                    $result->position = 0;
                    $result->relative = 'before';
                } else {
                    $result->position = $posnext;
                    $result->relative = 'before';
                }
            } else {
                if ($posnext === null) {
                    $result->position = $posprevious;
                    $result->relative = 'after';
                } else {
                    // Pick nearest.
                    if ($distprevious < $distnext) {
                        $result->position = $posprevious;
                        $result->relative = 'after';
                    } else {
                        $result->position = $posnext;
                        $result->relative = 'before';
                    }
                }
            }

        }

        return $result;
    }
}
