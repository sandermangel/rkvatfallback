<?php

class Redkiwi_Rkvatfallback_Model_Cache
{
    /**
     * @return bool
     */
    public function useCache()
    {
        return (bool)Mage::app()->useCache('rkvatfallback');
    }

    /**
     * @param string $vatNumber
     * @param Varien_Object $request
     */
    public function save($vatNumber, $request)
    {
        if ($this->useCache()) {
            $expiration = (new \DateTimeImmutable())->add(new \DateInterval('P1D'));

            Mage::app()->saveCache(serialize($request), 'rkvatfallback_validated_'.$vatNumber, ['VATVALIDATION_CACHE'], $expiration->getTimestamp());
        }
    }

    /**
     * @param string $vatNumber
     * @return bool
     */
    public function hasHit($vatNumber)
    {
        return (bool)$this->useCache() && Mage::app()->getCache()->load('rkvatfallback_validated_'.$vatNumber);
    }

    /**
     * @param string $vatNumber
     * @return Varien_Object
     */
    public function get($vatNumber)
    {
        $serializedObject = Mage::app()->getCache()->load('rkvatfallback_validated_'.$vatNumber);

        $unserializedObject = new Varien_Object();
        if (is_string($serializedObject)) {
            $unserializedObject = unserialize($serializedObject, ['Varien_Object']);
        }

        return $unserializedObject;
    }
}