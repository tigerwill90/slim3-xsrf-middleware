<?php
/**
 * This file is part of xcsrf-middleware package
 *
 * Copyright (c) 2017 Sylvain Muller
 *
 * Project home : https://github.com/tigerwill90/slim3-xsrf-middleware
 * License : MIT
 */

declare(strict_types=1);

namespace Tigerwill90\Middleware;

require __DIR__ . '/../vendor/autoload.php';

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Uri;
use Slim\Http\Headers;
use Slim\Http\Body;

use Firebase\JWT\JWT;
use Dflydev\FigCookies\Cookie;
use Dflydev\FigCookies\FigRequestCookies;

const KEY = "supersecretkeyyoushouldnotcommit";
const XSRF = "csrftoken";

class XsrfProtectionJwtTest extends \PHPUnit_Framework_TestCase {

    public function requestFactory() : Request {

        $uri = Uri::createFromString('http://dummy.apitest.com/api/signin');
        $headers = new Headers();
        $cookies = [];
        $serverParams = [];
        $body = new Body(fopen('php://temp', 'r+'));
        return new Request('GET', $uri, $headers, $cookies, $serverParams, $body);

     }

    /** @test */
    public function testAsArrayShouldMatchAndReturn200() : void {

        $request = $this->requestFactory();
        $response = new Response();

        $payload = [
            "uid" => 1,
            "csrf" => XSRF,
            "scope" => [1,0,1,1]
        ];

        $request = FigRequestCookies::set($request, Cookie::create('xCsrf', XSRF));

        $jwt = JWT::encode($payload, KEY, "HS256");
        $decoded = JWT::decode($jwt, KEY, ["HS256", "HS512", "HS384"]);

        $request = $request->withAttribute("token",$decoded);

        $xsrfProtection = new XsrfProtection([]);

        $next = function($request, $response) {
            $response->getBody()->write("Foo");
            return $response;
        };


        $response = $xsrfProtection($request,$response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Foo", $response->getBody());
    }

    /** @test */
    public function testAsJsonShouldMatchAndReturn200() : void{
        $request = $this->requestFactory();
        $response = new Response();

        $payload = [
            "uid" => 1,
            "csrf" => XSRF,
            "scope" => [1,0,1,1]
        ];

        $request = FigRequestCookies::set($request, Cookie::create('xCsrf', XSRF));

        $jwt = JWT::encode(json_encode($payload), KEY, "HS256");
        $decoded = JWT::decode($jwt, KEY, ["HS256", "HS512", "HS384"]);

        $request = $request->withAttribute("token",$decoded);

        $xsrfProtection = new XsrfProtection([]);

        $next = function($request, $response) {
            $response->getBody()->write("Foo");
            return $response;
        };


        $response = $xsrfProtection($request,$response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Foo", $response->getBody());
    }

    /** @test */
    public function testShouldNotMatchAndReturn401() : void {
        $request = $this->requestFactory();
        $response = new Response();

        $payload = [
            "uid" => 1,
            "csrf" => XSRF,
            "scope" => [1,0,1,1]
        ];

        $request = FigRequestCookies::set($request, Cookie::create('xCsrf', "xsrfNotMatch"));

        $jwt = JWT::encode(json_encode($payload), KEY, "HS256");
        $decoded = JWT::decode($jwt, KEY, ["HS256", "HS512", "HS384"]);

        $request = $request->withAttribute("token",$decoded);

        $xsrfProtection = new XsrfProtection([]);

        $next = function($request, $response) {
            $response->getBody()->write("Foo");
            return $response;
        };


        $response = $xsrfProtection($request,$response, $next);

        $this->assertEquals(401, $response->getStatusCode());
    }

    /** @test */
    public function testShouldNotFindClaimAndReturn401() : void {
        $request = $this->requestFactory();
        $response = new Response();

        $payload = [
            "uid" => 1,
            "noRightClaim" => XSRF,
            "scope" => [1,0,1,1]
        ];

        $request = FigRequestCookies::set($request, Cookie::create('xCsrf', "xsrfNotMatch"));

        $jwt = JWT::encode(json_encode($payload), KEY, "HS256");
        $decoded = JWT::decode($jwt, KEY, ["HS256", "HS512", "HS384"]);

        $request = $request->withAttribute("token",$decoded);

        $xsrfProtection = new XsrfProtection([]);

        $next = function($request, $response) {
            $response->getBody()->write("Foo");
            return $response;
        };


        $response = $xsrfProtection($request,$response, $next);

        $this->assertEquals(401, $response->getStatusCode());
    }

    /** @test */
    public function testShouldNotFindClaimInStringAndReturn401() : void {
        $request = $this->requestFactory();
        $response = new Response();

        $payload = "payloadshouldanassociativearray";

        $request = FigRequestCookies::set($request, Cookie::create('xCsrf', "xsrfNotMatch"));

        $jwt = JWT::encode(json_encode($payload), KEY, "HS256");
        $decoded = JWT::decode($jwt, KEY, ["HS256", "HS512", "HS384"]);

        $request = $request->withAttribute("token",$decoded);

        $xsrfProtection = new XsrfProtection([]);

        $next = function($request, $response) {
            $response->getBody()->write("Foo");
            return $response;
        };


        $response = $xsrfProtection($request,$response, $next);

        $this->assertEquals(401, $response->getStatusCode());
    }

    /** @test */
    public function testShouldNotFindCookieAndReturn401() : void {
        $request = $this->requestFactory();
        $response = new Response();

        $payload = "payloadshouldanassociativearray";

        $request = FigRequestCookies::set($request, Cookie::create('noRightCookieName', "xsrfNotMatch"));

        $jwt = JWT::encode(json_encode($payload), KEY, "HS256");
        $decoded = JWT::decode($jwt, KEY, ["HS256", "HS512", "HS384"]);

        $request = $request->withAttribute("token",$decoded);

        $xsrfProtection = new XsrfProtection([]);

        $next = function($request, $response) {
            $response->getBody()->write("Foo");
            return $response;
        };


        $response = $xsrfProtection($request,$response, $next);

        $this->assertEquals(401, $response->getStatusCode());
    }
}