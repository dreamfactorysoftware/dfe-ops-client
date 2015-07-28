<?php namespace DreamFactory\Enterprise\Console\Ops\Http\Middleware;

class OpsApiWrapper
{
    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        try {
            return $next($request);
        } catch (\Exception $_ex) {
            $_response = new \stdClass();
            $_response->success = false;
            $_response->error_code = $_ex->getCode();
            $_response->error_message = $_ex->getMessage();

            return $_response;
        }
    }
}