<?php

namespace Brexis\LaravelSSO\Http\Middleware;

use Closure;
use Brexis\LaravelSSO\ServerBrokerManager;
use Brexis\LaravelSSO\SessionManager;
use Brexis\LaravelSSO\Exceptions\UnauthorizedException;

use Illuminate\Support\Facades\Auth;

class ServerAuthenticate
{
    protected $broker;

    protected $session;

    public function __construct(ServerBrokerManager$broker, SessionManager $session)
    {
        $this->broker = $broker;
        $this->session = $session;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $guard = $guard ?: Auth::guard();

        $sid = $this->broker->getBrokerSessionId($request);
        $this->broker->validateBrokerSessionId($sid);

        if ($this->check($guard, $sid)) {
            return $next($request);
        }

        throw new UnauthorizedException(401, 'Unauthorized');
    }

    protected function check($guard, $sid)
    {
        $attrs = json_decode($this->session->getUserData($sid), true);

        if (!empty($attrs)) {
            $user = $guard->getProvider()->retrieveByCredentials($attrs);

            return $user && $guard->onceUsingId($user->id);
        }

        return false;
    }
}