<?php

include_once(BASEDIR . 'application'.DS.'plugins'.DS.'gerencianet'.DS.'libraries'.DS.'vendor'.DS.'autoload.php');

use Gerencianet\Exception\GerencianetException;
use Gerencianet\Gerencianet;


class GatewayGerencia{

    private $apiInstance = [];

    public function __construct($config){
        $this->apiInstance = $this->getGerencianetInstance($config);
    }
    
    
    private function getGerencianetInstance($config){
        try{
            $newInstance = Gerencianet::getInstance([
                    'client_id' => $config['client_id'],
                    'client_secret' => $config['client_secret'],
                    'certificate' => realpath(__DIR__ . "/certificate.pem"),
                    'sandbox' => ($config['sandbox'] == 1) ? 1 : 0,
                    'debug' =>  false,
                    'headers' => [
                        'x-skip-mtls-checking' => ($config['mtls'] == 0) ? 'true' : 'false'
                    ]
            ]);
            return $newInstance;

        } catch(GerencianetException $e){
            throw new Error($e->error);
        } catch (Exception $e){
            throw new Error($e->getMessage());
        }
    }
    
    
    public function createCharge($body){
        try{
            return $this->apiInstance->pixCreateImmediateCharge([], $body);
        } catch(GerencianetException $e){
            if($e->error != null){
                throw new Error($e->errorDescription);
            }
            else{
                throw new Error($e->getMessage());
            }
        } catch(Exception $e){
            throw new Error($e->getMessage());
        }
    }
    
    
    public function createPixKey(){
        try{
            return $this->apiInstance->pixCreateEvp();
        } catch(GerencianetException $e){
            if($e->error != null){
                throw new Error($e->errorDescription);
            }
            else{
                throw new Error($e->getMessage());
            }
        } catch(Exception $e){
            throw new Error($e->getMessage());
        }
    }
    
    
    public function generateQRCode($locationId){
        try{
            $params = [
                'id' => $locationId
            ];

            return $this->apiInstance->pixGenerateQRCode($params);

        } catch(GerencianetException $e){
            if($e->error != null){
                throw new Error($e->errorDescription);
            }
            else{
                throw new Error($e->getMessage());
            }
        } catch(Exception $e){
            throw new Error($e->getMessage());
        }
    }
    
    
    public function registerWebhook($pixKey, $url){
        try{
            $params = [
                "chave" => $pixKey
            ];

            $body = [
                "webhookUrl" => $url
            ];
            return $this->apiInstance->pixConfigWebhook($params, $body);

        } catch(GerencianetException $e){
            if($e->error != null){
                throw new Error($e->errorDescription);
            }
            else{
                throw new Error($e->getMessage());
            }
        } catch(Exception $e){
            throw new Error($e->getMessage());
        }
    }
}