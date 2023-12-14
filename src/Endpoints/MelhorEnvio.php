<?php

namespace MelhorEnvio\Endpoints;

use DateTime;
use MelhorEnvio\Exceptions\MelhorEnvioException;
use MelhorEnvio\Resoucers\Product;
use MelhorEnvio\Resoucers\User;
use MelhorEnvio\Resoucers\WorkingDays;

/**
 * Classe responsável por realizar as solicitações com a
 * plataforma melhor envio. É possivel calcular Frete e
 * gerar etiquetas.
 * ---------------------------------------------------------
 * Class MelhorEnvio
 * @package MelhorEnvio
 * @author igorcacerez
 */
class MelhorEnvio extends EndpointBase
{
    protected $defaultOptions = [
        'receipt'        => false,
        'own_hand'       => false,
        'non_commercial' => true,
    ];

    public function __construct(array $options = [])
    {
        $this->defaultOptions = array_merge($this->defaultOptions, $options);
    }

    /**
     * Método responsável por montar a url de redirecionamento de usuário, para o mesmo
     * autorizar o app a possui permissão sobre a conta do melhor envio.
     * Caso não seja informado a permissão, será solicitado as permissões padrao.
     * ---------------------------------------------------------------------------------------
     * @param null $state // Status a ser retornado
     * @param null $permission // Lista de permissões
     * @param bool $redirect // Informa se deve redirecionar ou retornar a url gerada
     * ---------------------------------------------------------------------------------------
     * @return string
     */
    public function requestAuthorization($state = null, $permission = null, $redirect = true)
    {
        // Verifica se informou permissão, senão habilita as padrões
        $permission = (!empty($permission) ? $permission : "cart-read cart-write companies-read companies-write coupons-read coupons-write notifications-read orders-read products-read products-write purchases-read shipping-calculate shipping-cancel shipping-checkout shipping-companies shipping-generate shipping-preview shipping-print shipping-share shipping-tracking ecommerce-shipping transactions-read users-read users-write");

        // Verifica se vai possui state
        $state = (!empty($state) ? "&state=" . $state : "");

        // Configura a url de redirecionamento
        $urlRedirect = $this->getBaseUri("oauth/authorize?client_id={$this->clientId}&redirect_uri={$this->urlCallback}&response_type=code{$state}&scope={$permission}");

        // Verifica se deve redirecionar ou retornar a url
        if ($redirect == true) {
            // Redireciona
            header("Location: " . $urlRedirect);
        } else {
            // Retorna a url configurada
            return $urlRedirect;
        }
    }

    /**
     * Método responsável por solicitar um token de acesso na plataforma do melhor envio.
     * Esse método é utilizado quando nunca foi solicitado um token antes.
     * ---------------------------------------------------------------------------------------
     *
     * -- Exemplo de retorno
     *
     *  (Array)
     *  [
     *     "error"  => [true ou false]  // Informa se teve algum erro na solicitação
     *     "data" => [
     *          "accessToken" => Token gerado e que será utilizado nas requisições
     *          "tokenValidate" => Data de validade do token (+ 30 dias)
     *          "refreshToken" => Token utilizado para renovar o token quando ele estiver vencido
     *      ]
     *  ]
     *
     * ---------------------------------------------------------------------------------------
     * @param $code // Codigo retornado pela plaforma quando solicita a permissão
     * ---------------------------------------------------------------------------------------
     * @return array
     */
    public function requestToken($code)
    {
        return $this->requestoOrRefreshToken($code);
    }

    /**
     * Método responsável por solicitar a renovação de um token de acesso já existente
     * na plataforma do melhor envio.
     * ---------------------------------------------------------------------------------------
     *
     * -- Exemplo de retorno
     *
     *  (Array)
     *  [
     *     "error"  => [true ou false]  // Informa se teve algum erro na solicitação
     *     "data" => [
     *          "accessToken" => Token gerado e que será utilizado nas requisições
     *          "tokenValidate" => Data de validade do token (+ 30 dias)
     *          "refreshToken" => Token utilizado para renovar o token quando ele estiver vencido
     *      ]
     *  ]
     *
     * ---------------------------------------------------------------------------------------
     * @param $refreshToken // Token de atualização do token de solicitação
     * ---------------------------------------------------------------------------------------
     * @return array
     */
    public function refreshToken($refreshToken)
    {
        return $this->requestoOrRefreshToken(null, $refreshToken);
    }

    public function getBalance()
    {
        // Variavel de retorno
        $retorno = ["error" => true, "data" => null];
        $dados = null;

        // Recupera o accessToken
        $accessToken = $this->accessToken;

        // Verifica se informou o token
        if (!empty($accessToken)) {
            // Url
            $url = $this->url . "api/v2/me/balance";


            // Header
            $header = ["Authorization: Bearer {$accessToken}", "Content-Type: application/json"];

            // Realiza a requisição
            $resultado = (new SendCurl($this->appName, $this->email))->request($url, "GET", $header, null);

            // Decodifica
            $resultado = json_decode($resultado);

            //dd($resultado);

            // Veririfca se deu erro
            if (!empty($resultado->errors) || !empty($resultado->error)) {
                // Adiciona o objeto
                $retorno["data"] = (!empty($resultado->errors) ? $resultado->errors : $resultado->error);
            } else {
                $dados = [
                    'balance'  => $resultado->balance,
                    'reserved' => $resultado->reserved,
                    'debts'    => $resultado->debts
                ];

                // Monta o retorno
                $retorno = [
                    "error"   => false,
                    "message" => "success",
                    "data"    => $dados
                ];
            }
        } else {
            $retorno["message"] = "Access Token não informado.";
        } // Error >> Token não informado

        // Retorno
        return $retorno;
    }

    public function getMe()
    {
        // Variavel de retorno
        $retorno = ["error" => true, "data" => null];
        $dados = null;

        // Recupera o accessToken
        $accessToken = $this->accessToken;

        // Verifica se informou o token
        if (!empty($accessToken)) {
            // Url
            $url = $this->url . "api/v2/me";


            // Header
            $header = ["Authorization: Bearer {$accessToken}", "Content-Type: application/json"];

            // Realiza a requisição
            $resultado = (new SendCurl($this->appName, $this->email))->request($url, "GET", $header, null);

            // Decodifica
            $resultado = json_decode($resultado);

            //dd($resultado);

            if (!empty($resultado->message) && $resultado->message == "Unauthenticated.") {
                $retorno["message"] = "Token Expirado";
                return $retorno;
            }


            // Veririfca se deu erro
            if (!empty($resultado->errors) || !empty($resultado->error)) {
                // Adiciona o objeto
                $retorno["data"] = (!empty($resultado->errors) ? $resultado->errors : $resultado->error);
            } else {
                $dados = [
                    'user'      => $resultado->firstname . ' ' . $resultado->lastname,
                    'shipments' => $resultado->limits->shipments . '/' . $resultado->limits->shipments_available,
                ];

                // Monta o retorno
                $retorno = [
                    "error"   => false,
                    "message" => "success",
                    "data"    => $dados
                ];
            }

        } else {
            $retorno["message"] = "Access Token não informado.";
        } // Error >> Token não informado

        // Retorno
        return $retorno;
    }

    /**
     * Método responsável por calcular um frete de uma origem a um destino
     * com muitos ou um unico produto.
     * ---------------------------------------------------------------------------------------
     *
     * -- Exemplo de retorno
     *
     *  (Array)
     *  [
     *     "error"  => [true ou false]  // Informa se teve algum erro na solicitação
     *     "message" => Informação sobre o erro dado
     *     "data" => [
     *          "company" => [
     *              "name" => Nome da transportadora
     *              "image" => Imagem da logo da transportadora
     *           ],
     *          "service" => Nome do serviço (ex: Pac, Sedex...)
     *          "timeDays" => Prazo em dias para entrega
     *          "code" => Codigo do servico
     *          "packages" => Lista dos pacotes que serão enviados
     *      ]
     *  ]
     *
     * ---------------------------------------------------------------------------------------
     * @param $cepOrigem
     * @param $cepDestino
     * @param Product $products
     * ----------------------------------------------------------------------
     * @return array
     */
    public function calculate($cepOrigem, $cepDestino, Product $products, array $services = [], array $options = [])
    {
        $dados = [];

        if (empty($this->accessToken))
            throw new MelhorEnvioException('Autenticação é obrigatória (accessToken).', null, 401);

        $cepOrigem = preg_replace("/[^0-9]/", "", $cepOrigem);
        $cepDestino = preg_replace("/[^0-9]/", "", $cepDestino);

        $produtos = $products->getProducts();

        $payload = [
            'from' => [
                'postal_code' => $cepOrigem
            ],
            'to'   => [
                'postal_code' => $cepDestino
            ],
        ];

        $payload['options'] = $this->defaultOptions;

        if ($services)
            $payload['services'] = implode(',', $services);

        $payload['options'] = array_merge($this->defaultOptions, $options);

        foreach ($produtos as $produto) {
            $payload['products'][] = $produto;
        }

        $response = $this->request("POST", 'api/v2/me/shipment/calculate', ['json' => $payload]);
        $result = $response->getResponse();

        $dataNow = new DateTime('now');

        if ((is_array($result) || is_object($result)) && $result) {
            $result = is_object($result) ? [$result] : $result;
            foreach ($result as $res) {
                if (empty($res->error)) {
                    $dados[] = [
                        "company"       => [
                            "name"  => $res->company->name,
                            "image" => $res->company->picture
                        ],
                        "code"          => $res->id,
                        "service"       => $res->name,
                        "value"         => $res->custom_price,
                        "timeDays"      => $res->custom_delivery_time,
                        "deliveryRange" => $res->custom_delivery_range,
                        "deadline"      => [
                            "min" => WorkingDays::getWorkingDays($dataNow, $res->custom_delivery_range->min),
                            "max" => WorkingDays::getWorkingDays($dataNow, $res->custom_delivery_range->max)
                        ],
                        "packages"      => $res->packages
                    ];
                }
            }

            return $dados;

        } else {
            if ($result == "Unauthenticated") {
                throw new MelhorEnvioException('Unauthenticated', $response);
            } else {
                throw new MelhorEnvioException("Ocorreu um erro ao calcular.", $response);
            }
        }
    }


    /**
     * Método resposável por solicitar um a compra de uma etiqueta na
     * plataforma do melhor envio. Será retornado os ids da solicitadação
     * que apos deverá ser realizada a compra.
     * ----------------------------------------------------------------------
     * Exemplo do Packges a ser enviado:
     *
     * (Array)
     * [
     *    "height" => Altura
     *    "width" => Largura
     *    "length" => Comprimento
     *    "weight" => Peso
     * ]
     *
     * ----------------------------------------------------------------------
     * @param User $destinario
     * @param User $remetente
     * @param Product $products
     * @param array $packages
     * @param $codService
     * @param $idPedido
     * @param null $urlPedido
     * ----------------------------------------------------------------------
     * @return array
     */
    public function requestBuyTag(User $destinario, User $remetente, Product $products, array $packages, $codService, array $options = []): array
    {
        $valorTotal = 0;
        $payload = [];

        if (empty($this->accessToken))
            throw new MelhorEnvioException('Autenticação é obrigatória (accessToken).', null, 401);

        $produtos = $products->getProducts();

        $payload['service'] = $codService;

        $payload['from'] = $remetente->toArray();
        $payload['to'] = $destinario->toArray();

        foreach ($produtos as $produto) {
            // Soma o total
            $valorTotal = $valorTotal + ($produto['quantity'] * $produto['insurance_value']);

            $payload['products'][] = [
                "name"          => $produto['name'],
                "quantity"      => $produto['quantity'],
                "unitary_value" => $produto['insurance_value']
            ];
        }

        // Opcoes
        $payload['options']['insurance_value'] = $valorTotal;
        $payload['options'] = $this->defaultOptions;

        if ($options)
            $payload['options'] = array_merge($this->defaultOptions, $options);

        $payload['volumes'] = $packages;

        $response = $this->request("POST", 'api/v2/me/cart', ['json' => $payload]);
        $result = $response->getResponse();

        if (!empty($result->errors) || !empty($result->error)) {
            throw new MelhorEnvioException((!empty($result->errors) ? $result->errors : $result->error), $response);
        }

        return is_array($result) ? $result : [$result];
    }

    /**
     * Método responsável por processar a compra das etiquetas já solicitadas
     * anteriormente e apos pago solicita a geração do numero da etiqueta.
     * ----------------------------------------------------------------------
     * @param array $tags
     * @return array Sua compra com as etiquetas e outras informações
     */
    public function processBuyTag(array $tags): array
    {
        $payload = [];

        $payload['orders'] = $this->getTagsIds($tags);

        $purchasedTags = $this->request("POST", 'api/v2/me/shipment/checkout', ['json' => $payload])->getResponse();

        return (array)$purchasedTags->purchase;
    }

    /**
     * Método responsável por solicitar o arquivo para impressão
     * da etiqueta.
     * ----------------------------------------------------------------------
     * @param array $tags
     * @return array
     */
    public function printTag(array $tags): array
    {
        $payload = [];
        $payload['mode'] = "public";
        $payload['orders'] = $tags;

        $urls = $this->request("POST", 'api/v2/me/shipment/print', ['json' => $payload])->getResponse();

        return (array)$urls;
    }

    /**
     * Método responsável por solicitar os códigos de rastreio
     * de uma etiqueta previamente gerada.
     * ----------------------------------------------------------------------
     * Exemplo retorno em caso de sucesso:
     *
     * (Array)
     * [
     *    "error" => false,
     *    "message" => "success",
     *    "data" => [
     *        {"tracking" => "CODIGO DE RASTREIO"}
     *    ]
     * ]
     *
     * ----------------------------------------------------------------------
     * @param array $tags
     * @return array trackings
     */
    public function getTracking(array $tags): array
    {
        $payload = [];
        $payload['orders'] = $tags;

        $tags = $this->request("POST", 'api/v2/me/shipment/tracking', ['json' => $payload])->getResponse();

        return (array)$tags;
    }

    /**
     * Método interno responsável por realizar a configuração e a requisição
     * tanto para gerar um token novo como para renovar um token já existente.
     * --------------------------------------------------------------------------
     * @param null $code
     * @param null $refreshToken
     * @return array
     */
    protected function requestoOrRefreshToken($code = null, $refreshToken = null)
    {
        $payload = [
            "grant_type"    => "authorization_code",
            "client_id"     => $this->clientId,
            "client_secret" => $this->secretKey,
        ];

        if (!empty($code)) {
            $payload["redirect_uri"] = $this->urlCallback;
            $payload["code"] = $code;
        } else {
            $payload["grant_type"] = "refresh_token";
            $payload["refresh_token"] = $refreshToken;
        }

        $resposta = $this->request("POST", "oauth/token", ['json' => $payload])->getResponse();

        $retorno = [
            "accessToken"   => $resposta->access_token,
            "tokenValidate" => date("Y-m-d", strtotime("+28 days")),
            "refreshToken"  => $resposta->refresh_token
        ];

        return $retorno;
    }

    /**
     * Método responsável por solicitar a geração de uma etiqueta
     * apos ela estiver comprada.
     * --------------------------------------------------------------------------
     * @param array $purchases
     * --------------------------------------------------------------------------
     * @return array
     */
    public function requestTag(array $purchases): array
    {
        $payload = ['orders' => $this->getPurchaseOrdersIds($purchases)];
        $response = $this->request("POST", 'api/v2/me/shipment/generate', ['json' => $payload])->getResponse();
        return (array)$response;
    }

    public function cancelTag(string $tag, string $description)
    {
        $payload = [
            'order' => [
                'id'          => $tag,
                'reason_id'   => 2,
                'description' => $description,
            ]
        ];

        $response = $this->request("POST", 'api/v2/me/shipment/cancel', ['json' => $payload]);
        $result = $response->getResponse();

        if (!$result->{$tag}->canceled)
            throw new MelhorEnvioException('Não foi possível cancelar esta etiqueta', $response);
    }

    public function getServices()
    {
        $response = $this->request("GET", 'api/v2/me/shipment/services')->getResponse();
        return (array)$response;
    }

    public function getCompanies()
    {
        $response = $this->request("GET", 'api/v2/me/shipment/companies')->getResponse();
        return (array)$response;
    }

    protected function getTagsIds(array $tags): array
    {
        $ids = [];

        foreach ($tags as $tag) {
            $ids[] = $tag->id;
        }

        return $ids;
    }

    protected function getPurchaseOrdersIds(array $purchase): array
    {
        $ids = [];

        foreach ($purchase['orders'] as $order) {
            $ids[] = $order->id;
        }

        return $ids;
    }
}
