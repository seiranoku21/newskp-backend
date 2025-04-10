<?php
namespace App\Http\Middleware;
use Illuminate\Http\Request;
use Closure;
use App\Models\permissions;
use Illuminate\Support\Facades\Config;

class Rbac
{
	public $excludePages = ["/", "home", "account"];

	/**
	* Handle an incoming request.
	*
	* @param  \Illuminate\Http\Request  $request
	* @param  \Closure  $next
	* @return mixed
	*/
	public function handle($request, Closure $next)
	{
		$page = $request->segment(2, "home");
		$action = $request->segment(3, "index");
		$user = $request->user();
		
		$page_action = strtolower("$page/$action");
		$authRequired = !in_array($page, $this->excludePages);
		if ($authRequired  && !$user->canAccess($page_action)) {
			return response("Forbidden", 403);
		}
		return $next($request);
	}
}
