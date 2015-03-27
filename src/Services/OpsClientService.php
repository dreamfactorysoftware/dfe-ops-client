<?php
namespace DreamFactory\Enterprise\Console\Ops\Services;

use DreamFactory\Enterprise\Common\Services\BaseService;
use DreamFactory\Library\Utility\IfSet;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class OpsClientService extends BaseService
{
    //******************************************************************************
    //* Constants
    //******************************************************************************

    /**
     * @type int The API version to target
     */
    const API_VERSION = 1;

    //*************************************************************************
    //* Members
    //*************************************************************************

    /**
     * @var Client
     */
    protected $_client = null;
    /**
     * @type string
     */
    protected $_signature;
    /**
     * @type string
     */
    protected $_clientId;

    //*************************************************************************
    //* Methods
    //*************************************************************************

    /**
     * @param string $appServer    The hostname of the app server to use
     * @param int    $port         The port on which to connect
     * @param string $clientId     Your application's client ID
     * @param string $clientSecret Your application's secret ID
     */
    public function create( $appServer, $clientId, $clientSecret, $port = 80 )
    {
        $this->_signature = $this->_generateSignature( $clientId, $clientSecret );
        $this->_clientId = $clientId;

        $_endpoint = ( 443 === $port ? 'https' : 'http' ) . '://' . trim( $appServer, '/ ' ) . '/api/v' . static::API_VERSION . '/ops';

        //  Check the endpoint...
        if ( false === parse_url( $_endpoint ) )
        {
            throw new \InvalidArgumentException( 'The specified endpoint is not valid.' );
        }

        //  Create our client
        $this->_client = new Client( ['base_url' => $_endpoint] );
    }

    /**
     * @return array|bool
     */
    public function instances()
    {
        return $this->post( 'instances' );
    }

    /**
     * Register a new user
     *
     * @param array $payload
     *
     * @return bool
     */
    public function register( array $payload )
    {
        if ( empty( $payload ) || null === ( $_email = IfSet::get( $payload, 'email' ) ) )
        {
            $this->error( 'register: empty payload or no email address given.' );

            return false;
        }

        if ( false === ( $_result = $this->post( 'register', $payload ) ) )
        {
            $this->error( 'register: cannot connect to server' );

            return false;
        }

        $_code = 1;

        if ( Response::HTTP_OK != $_code && Response::HTTP_CREATED != $_code )
        {
            $this->error( 'register: error ' . $_code . ' registering new user' );

            return false;
        }

        $this->info( 'register: user creation success ' . print_r( $_result, true ) );

        return true;
    }

    public function provision( array $payload )
    {
    }

    /**
     * @param string $uri
     * @param array  $payload
     * @param array  $options
     *
     * @return array|bool
     */
    public function get( $uri, $payload = [], $options = [] )
    {
        return $this->_apiCall( $uri, $payload, $options, Request::METHOD_GET );
    }

    /**
     * @param string $uri
     * @param array  $payload
     * @param array  $options
     *
     * @return array|bool
     */
    public function post( $uri, $payload = [], $options = [] )
    {
        return $this->_apiCall( $uri, $payload, $options, Request::METHOD_POST );
    }

    /**
     * @param string $uri
     * @param array  $payload
     * @param array  $options
     *
     * @return array|bool
     */
    public function delete( $uri, $payload = [], $options = [] )
    {
        return $this->_apiCall( $uri, $payload, $options, Request::METHOD_DELETE );
    }

    /**
     * @param string $uri
     * @param array  $payload
     * @param array  $options
     *
     * @return array|bool
     */
    public function put( $uri, $payload = [], $options = [] )
    {
        return $this->_apiCall( $uri, $payload, $options, Request::METHOD_PUT );
    }

    /**
     * @param string $url
     * @param array  $payload
     * @param array  $options
     * @param string $method
     *
     * @return bool|array
     */
    protected function _apiCall( $url, $payload = [], $options = [], $method = Request::METHOD_POST )
    {
        if ( !isset( $options['body'] ) )
        {
            $options['body'] = [];
        }

        $options['body'] = array_merge( $options['body'], $this->_signPayload( $payload ) );

        try
        {
            $_request = $this->_client->createRequest( $method, $url, $options );
            $_response = $this->_client->send( $_request );

            return $_response->json();
        }
        catch ( RequestException $_ex )
        {
            if ( $_ex->hasResponse() )
            {
                return $_ex->getResponse();
            }
        }

        return false;
    }

    /**
     * @param array $payload
     *
     * @return array
     */
    protected function _signPayload( array $payload )
    {
        return array_merge(
            array(
                'user-id'      => Auth::user()->id,
                'client-id'    => $this->_clientId,
                'access-token' => $this->_signature,
            ),
            $payload ?: []
        );

    }

    /**
     * @param string $clientId
     * @param string $clientSecret
     *
     * @return string
     */
    protected function _generateSignature( $clientId, $clientSecret )
    {
        return hash_hmac( 'sha256', $clientId, $clientSecret );
    }
}
