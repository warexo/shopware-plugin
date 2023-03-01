<?php

namespace Warexo\Core\Framework\DataAbstractionLayer\FieldSerializer;

use Shopware\Core\Framework\DataAbstractionLayer\Field\Field;
use Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer\AbstractFieldSerializer;
use Shopware\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;

/**
 * Shopware uses strip_tags to sanitize HTML in the backend.
 * As this strips less than chars not followed by a space we will add a space if needed
 */
class StringFieldSerializer extends AbstractFieldSerializer
{
    private AbstractFieldSerializer $decorated;

    public function __construct(AbstractFieldSerializer $decorated)
    {
        $this->decorated = $decorated;
    }

    public function encode(
        Field $field,
        EntityExistence $existence,
        KeyValuePair $data,
        WriteParameterBag $parameters
    ): \Generator {

        if (!$field->is(AllowHtml::class)) {
            $data->setValue(preg_replace('/<(\w)/m', '< $1', html_entity_decode((string) $data->getValue())));
        }

        return $this->decorated->encode($field, $existence, $data, $parameters);
    }

    public function decode(Field $field, $value): ?string
    {
        return $this->decorated->decode($field, $value);
    }
}