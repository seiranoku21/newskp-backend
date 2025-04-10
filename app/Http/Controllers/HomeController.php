<?php 
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;



/**
 * Home Controller
 * @category  Controller
 */
class HomeController extends Controller{
	/**
     * Index Action
     * @return View
     */
	function index(){
		return view("welcome");
	}
	
}
