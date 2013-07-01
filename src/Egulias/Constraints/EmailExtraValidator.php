<?php

namespace Egulias\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Egulias\EmailValidator\EmailValidator;

/**
 * EmailExtraValidator
 *
 * @author Eduardo Gulias Davis <me@egulias.com>
 */
class EmailExtraValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_scalar($value) && !(is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $validator = new EmailValidator();
        $valid = $validator->isValid($value, $constraint->checkMX, $constraint->strict);

        if ($constraint->verbose) {
            $constraint->message .= implode(',', $this->getWarnings());
        }

        if (!$valid) {
            if ($constraint->verbose) {
                $constraint->message .= "Error code: {$validator->getError()}";
            }
            $this->context->addViolation($constraint->message, array('{{ value }}' => $value));
        }


    }
}
