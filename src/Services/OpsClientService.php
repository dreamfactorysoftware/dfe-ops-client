<?php namespace DreamFactory\Enterprise\Console\Ops\Services;

use DreamFactory\Enterprise\Common\Services\BaseService;
use DreamFactory\Enterprise\Common\Traits\Guzzler;
use DreamFactory\Enterprise\Database\Enums\ProvisionStates;
use Illuminate\Http\Response;

class OpsClientService extends BaseService
{
    //******************************************************************************
    //* Traits
    //******************************************************************************

    use Guzzler;

    //*************************************************************************
    //* Methods
    //*************************************************************************

    /**
     * Initialize and set up the transport layer
     *
     * @param string $url         The url of the app server to use
     * @param array  $credentials Any connection credentials
     * @param array  $config      Any GuzzleHttp options
     *
     * @return $this
     */
    public function connect($url, $credentials = [], $config = [])
    {
        return $this->createRequest($url,
            [
                'client-id'     => array_get($credentials, 'client-id'),
                'client-secret' => array_get($credentials, 'client-secret'),
            ],
            $config);
    }

    /**
     * Retrieve a list of all provisioners available
     *
     * @param array $options Any guzzlehttp options
     * @param bool  $object  If true, results are returned as an object. Otherwise data is in array form
     *
     * @return \stdClass
     */
    public function provisioners($options = [], $object = true)
    {
        return $this->guzzlePost('provisioners', [], $options, $object);
    }

    /**
     * Retrieve a list of all instances for the user
     *
     * @param array $options Any guzzlehttp options
     * @param bool  $object  If true, results are returned as an object. Otherwise data is in array form
     *
     * @return \stdClass
     */
    public function instances($options = [], $object = true)
    {
        return $this->guzzlePost('instances', [], $options, $object);
    }

    /**
     * Retrieve status of an instance
     *
     * @param string $id
     * @param array  $options Any guzzlehttp options
     * @param bool   $object  If true, results are returned as an object. Otherwise data is in array form
     *
     * @return \stdClass
     * @api /api/v1/ops/status/{id}
     */
    public function status($id, $options = [], $object = true)
    {
        $_status = $this->guzzlePost('status', ['id' => $id], $options, $object);
        $_status->deleted = false;

        if (!$_status->success) {
            if (isset($_status->code, $_status->message)) {
                $_status->provisioned = false;
                $_status->deprovisioned = false;
                $_status->trial = false;
                $_status->instanceState = ProvisionStates::DEPROVISIONED;

                if (!$_status->deleted && Response::HTTP_NOT_FOUND == $_status->code) {
                    $_status->deleted = true;
                }
            }
        }

        return $_status;
    }

    /**
     * Register a new user
     *
     * @param array $payload
     * @param array $options Any guzzlehttp options
     * @param bool  $object  If true, results are returned as an object. Otherwise data is in array form
     *
     * @return bool
     */
    public function register(array $payload = [], $options = [], $object = true)
    {
        if (empty($payload) || null === ($_email = array_get($payload, 'email'))) {
            $this->error('register: empty payload or no email address given.');

            return false;
        }

        if (false === ($_result = $this->guzzlePost('register', $payload, $options, $object))) {
            $this->error('register: cannot connect to server');

            return false;
        }

        $_code = 1;

        if (Response::HTTP_OK != $_code && Response::HTTP_CREATED != $_code) {
            $this->error('error ' . $_code . ' registering new user');

            return false;
        }

        $this->info('user creation success ' . print_r($_result, true));

        return true;
    }

    /**
     * Provision a new instance
     *
     * @param array $payload
     * @param array $options Any guzzlehttp options
     * @param bool  $object  If true, results are returned as an object. Otherwise data is in array form
     *
     * @return array|bool|\stdClass
     */
    public function provision(array $payload, $options = [], $object = true)
    {
        if (false === ($_response = $this->guzzlePost('provision', $payload, $options, $object))) {
        }
    }

    /**
     * Provision a new instance
     *
     * @param array $payload
     * @param array $options Any guzzlehttp options
     * @param bool  $object  If true, results are returned as an object. Otherwise data is in array form
     *
     * @return array|bool|\stdClass
     */
    public function deprovision(array $payload, $options = [], $object = true)
    {
        return $this->guzzlePost('deprovision', $payload, $options, $object);
    }
}
