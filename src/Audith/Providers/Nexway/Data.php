<?php
namespace Audith\Providers\Nexway;

/**
 * @author    Shahriyar Imanov <shehi@imanov.me>
 */
abstract class Data
{
    private static $mapOfSoapResponseTypesToCustomResponseObjects = array(
        'getCategoriesCategoryResponseType'            => array('nameSpace' => '\Audith\Providers\Nexway\Data\Response\CatalogApi\getCategories'),
        'getCategoriesSubCategoryResponseType'         => array('nameSpace' => '\Audith\Providers\Nexway\Data\Response\CatalogApi\getCategories\subcategory'),
        'getOperatingSystemsOsResponseType'            => array('nameSpace' => '\Audith\Providers\Nexway\Data\Response\CatalogApi\getOperatingSystems'),
        'getStockStatusResponseType'                   => array('nameSpace' => '\Audith\Providers\Nexway\Data\Response\OrderApi\getStockStatus'),
        'getStockStatusproductStatusResponseType'      => array('nameSpace' => '\Audith\Providers\Nexway\Data\Response\OrderApi\getStockStatus\productStatus'),
        'getCrossUpSellResponseType'                   => array('nameSpace' => '\Audith\Providers\Nexway\Data\Response\OrderApi\getCrossUpSell'),
        'getCrossUpSellProductReturnResponseType'      => array('nameSpace' => '\Audith\Providers\Nexway\Data\Response\OrderApi\getCrossUpSell\productsReturn'),
        'createResponseType'                           => array('nameSpace' => '\Audith\Providers\Nexway\Data\Response\OrderApi\create'),
        'createOrderLineResponseType'                  => array('nameSpace' => '\Audith\Providers\Nexway\Data\Response\OrderApi\create\orderLines'),
        'createLineItemResponseType'                   => array('nameSpace' => '\Audith\Providers\Nexway\Data\Response\OrderApi\create\orderLines\lineItems'),
        'createSerialResponseType'                     => array('nameSpace' => '\Audith\Providers\Nexway\Data\Response\OrderApi\create\orderLines\lineItems\serials'),
        'createFileResponseType'                       => array('nameSpace' => '\Audith\Providers\Nexway\Data\Response\OrderApi\create\orderLines\files'),
        'createDownloadManagerResponseType'            => array('nameSpace' => '\Audith\Providers\Nexway\Data\Response\OrderApi\create\downloadManager'),
        'getDataResponseType'                          => array('nameSpace' => '\Audith\Providers\Nexway\Data\Response\OrderApi\getData'),
        'getDataOrderLineResponseType'                 => array('nameSpace' => '\Audith\Providers\Nexway\Data\Response\OrderApi\getData\orderLines'),
        'getDataLineItemResponseType'                  => array('nameSpace' => '\Audith\Providers\Nexway\Data\Response\OrderApi\getData\orderLines\lineItems'),
        'getDataSerialResponseType'                    => array('nameSpace' => '\Audith\Providers\Nexway\Data\Response\OrderApi\getData\orderLines\lineItems\serials'),
        'getDataFileResponseType'                      => array('nameSpace' => '\Audith\Providers\Nexway\Data\Response\OrderApi\getData\orderLines\files'),
        'getDataDownloadManagerResponseType'           => array('nameSpace' => '\Audith\Providers\Nexway\Data\Response\OrderApi\getData\downloadManager'),
        'getDownloadInfoResponseType'                  => array('nameSpace' => '\Audith\Providers\Nexway\Data\Response\OrderApi\getDownloadInfo'),
        'getDownloadInfoOrderLineDownloadResponseType' => array('nameSpace' => '\Audith\Providers\Nexway\Data\Response\OrderApi\getDownloadInfo\orderLines'),
        'getDownloadInfoFileDownloadResponseType'      => array('nameSpace' => '\Audith\Providers\Nexway\Data\Response\OrderApi\getDownloadInfo\orderLines\files'),
        'calculateVATResponseType'                     => array('nameSpace' => '\Audith\Providers\Nexway\Data\Response\OrderApi\calculateVAT'),
        'calculateVATVatResponseType'                  => array('nameSpace' => '\Audith\Providers\Nexway\Data\Response\OrderApi\calculateVAT\vat'),
        'getOrderHistoryResponseType'                  => array('nameSpace' => '\Audith\Providers\Nexway\Data\Response\CustomerApi\getOrderHistory'),
        'getOrderHistoryOrderHistoryResponseType'      => array('nameSpace' => '\Audith\Providers\Nexway\Data\Response\CustomerApi\getOrderHistory\ordersHistory')
    );


    /**
     * Maps PHP-DOMNode to Provider Response Object
     *
     * @param mixed $sourceResponsePackage
     *
     * @return self
     */
    public static function mapNexwaySoapResponseObjectToOurCustomResponseObject($sourceResponsePackage)
    {
        $destinationObject = new Data\Response();
        $destinationObject = self::parseSoapResponsePackageRecursive($sourceResponsePackage, $destinationObject);

        //---------
        // Return
        //---------

        return $destinationObject;
    }


    /**
     * @param   array|\stdClass $responseObject
     * @param   Data\Response   $destinationObject
     *
     * @return  array|Data\Response
     */
    private static function parseSoapResponsePackageRecursive($responseObject, $destinationObject)
    {
        if (is_object($responseObject)) {
            foreach ($responseObject as $_key => $_value) {
                if (is_object($_value)) {
                    $_objectProperties        = array_keys(get_object_vars($_value));
                    $_countOfObjectProperties = count($_objectProperties);
                    if ($_countOfObjectProperties == 1 and array_key_exists($_objectProperties[0], self::$mapOfSoapResponseTypesToCustomResponseObjects)) {
                        $_destinationObjectNamespace = self::$mapOfSoapResponseTypesToCustomResponseObjects[$_objectProperties[0]]['nameSpace'];
                        if (is_array($_value->{$_objectProperties[0]})) {
                            $destinationObject->{$_key} = self::parseSoapResponsePackageRecursive($_value->{$_objectProperties[0]}, $_destinationObjectNamespace);
                        } elseif (is_object($_value->{$_objectProperties[0]})) {
                            // Bug: SOAPClient converts nodes with single children to an object, instead of an array of objects as it usually does with a node with multiple children.
                            $destinationObject->{$_key} = self::parseSoapResponsePackageRecursive(array($_value->{$_objectProperties[0]}), $_destinationObjectNamespace);
                        }
                    }
                } else {
                    $destinationObject->{$_key} = $_value;
                }
            }
        } elseif (is_array($responseObject)) {
            $destinationArray = array();
            foreach ($responseObject as $_key => $_value) {
                $_destinationObjectTemplate = is_string($destinationObject)
                    ? new $destinationObject()
                    : $destinationObject; // Let's make sure it's an object
                $destinationArray[$_key]    = self::parseSoapResponsePackageRecursive($_value, $_destinationObjectTemplate);
            }

            $destinationObject = $destinationArray;
        }

        return $destinationObject;
    }


    /**
     * Unsets certain list of keys from an array, in a recursive fashion
     *
     * @param array $array
     * @param array $keysToRemove
     *
     * @return void
     */
    protected static function unsetRecursive(&$array, $keysToRemove)
    {
        if (!is_array($keysToRemove)) {
            $keysToRemove = array($keysToRemove);
        }
        foreach ($array as $key => &$value) {
            if (in_array($key, $keysToRemove, true)) {
                if (is_object($array)) {
                    unset($array->$key);
                } elseif (is_array($array)) {
                    unset($array[$key]);
                }
            } else if (is_array($value) or is_object($value)) {
                self::unsetRecursive($value, $keysToRemove);
            }
        }
    }
}