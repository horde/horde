<?php
/**
 * Credit card number
 */
class Horde_Form_Type_creditcard extends Horde_Form_Type {

    function isValid($var, $vars, $value, &$message)
    {
        if (empty($value) && $var->required) {
            $message = Horde_Model_Translation::t("This field is required.");
            return false;
        }

        if (!empty($value)) {
            /* getCardType() will also verify the checksum. */
            $type = $this->getCardType($value);
            if ($type === false || $type == 'unknown') {
                $message = Horde_Model_Translation::t("This does not seem to be a valid card number.");
                return false;
            }
        }

        return true;
    }

    function getChecksum($ccnum)
    {
        $len = strlen($ccnum);
        if (!is_long($len / 2)) {
            $weight = 2;
            $digit = $ccnum[0];
        } elseif (is_long($len / 2)) {
            $weight = 1;
            $digit = $ccnum[0] * 2;
        }
        if ($digit > 9) {
            $digit = $digit - 9;
        }
        $i = 1;
        $checksum = $digit;
        while ($i < $len) {
            if ($ccnum[$i] != ' ') {
                $digit = $ccnum[$i] * $weight;
                $weight = ($weight == 1) ? 2 : 1;
                if ($digit > 9) {
                    $digit = $digit - 9;
                }
                $checksum += $digit;
            }
            $i++;
        }

        return $checksum;
    }

    function getCardType($ccnum)
    {
        $sum = $this->getChecksum($ccnum);
        $l = strlen($ccnum);

        // Screen checksum.
        if (($sum % 10) != 0) {
            return false;
        }

        // Check for Visa.
        if ((($l == 16) || ($l == 13)) &&
            ($ccnum[0] == 4)) {
            return 'visa';
        }

        // Check for MasterCard.
        if (($l == 16) &&
            ($ccnum[0] == 5) &&
            ($ccnum[1] >= 1) &&
            ($ccnum[1] <= 5)) {
            return 'mastercard';
        }

        // Check for Amex.
        if (($l == 15) &&
            ($ccnum[0] == 3) &&
            (($ccnum[1] == 4) || ($ccnum[1] == 7))) {
            return 'amex';
        }

        // Check for Discover (Novus).
        if (strlen($ccnum) == 16 &&
            substr($ccnum, 0, 4) == '6011') {
            return 'discover';
        }

        // If we got this far, then no card matched.
        return 'unknown';
    }

}
