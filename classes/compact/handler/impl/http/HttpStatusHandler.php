<?php
namespace compact\handler\impl\http;

use compact\handler\IHander;
use compact\Context;
use compact\logging\Logger;

/**
 *
 * @author eaboxt
 *        
 */
class HttpStatusHandler implements IHander
{
    /*
     * (non-PHPdoc) @see \compact\handler\IHander::accept()
     */
    public function accept($object)
    {
        return $object instanceof HttpStatus;
    }
    
    /*
     * (non-PHPdoc) @see \compact\handler\IHander::handle()
     */
    public function handle($object)
    {
        /* @var $object \compact\handler\impl\http\HttpStatus */
        $context = Context::get();
        $response = Context::get()->http()->getResponse();
        
        $response->setStatusCode( $object->getHttpCode() );
        
        Logger::get()->logFine("Got http status " . $object->getHttpCode() );
        
        // add extra headers
        $extraHeaders = $object->getExtraHeaders();
        if ($extraHeaders){
            foreach ($extraHeaders as $header => $value){
                $response->addHeader($header, $value);
            }
        }
        
        if ($object->getContent())
        {
            $handler = Context::get()->getHandler( $object->getContent() );
            if ($handler)
            { /* @var $handler \compact\handler\IHander */
                return $handler->handle( $object->getContent() );
            }
        }
        
        return $object;
    }
}