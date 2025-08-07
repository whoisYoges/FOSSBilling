<?php

class BBTestCase extends PHPUnit\Framework\TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        $refl = new ReflectionObject($this);
        foreach ($refl->getProperties() as $prop) {
            if (!$prop->isStatic() && !str_starts_with($prop->getDeclaringClass()->getName(), 'PHPUnit_') && !str_starts_with($prop->getName(), '__')) {
                $prop->setAccessible(true);
                $prop->setValue($this, null);
            }
        }
    }
}
