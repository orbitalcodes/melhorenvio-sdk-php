<?php


namespace MelhorEnvio\Resoucers;

use stdClass;

/**
 * Class User
 *
 * Classe responsável por recuperar as informações de usuario
 * sendo Remetente ou Destinatario
 *
 * @package MelhorEnvio
 */
class User
{

    /**
     * Armazena os dados do usuario.
     *
     * @var array
     */
    private $user = [];

    /**
     * Armazena no objeto do usuario as informações pessoais
     *
     * @param $nome
     * @param $email
     * @param $telefone
     */
    public function setInformacaoPessoal($nome, $email, $telefone)
    {
        // Atribui ao objeto
        $this->user['name'] = $nome;
        $this->user['email'] = $email;

        // Limpa o telefone
        $this->user['phone'] = preg_replace("/[^0-9]/", "", $telefone);
    }


    /**
     * Método responsável por armazenar as informações de documentos
     * tanto para pessoa juridica como fisica.
     *
     * @param null $cpf
     * @param null $cnpj
     * @param null $ie
     */
    public function setDocumentos($cpf = null, $cnpj = null, $ie = null)
    {
        // Armazena o cpf que é obrigatorio
        $this->user['document'] = preg_replace("/[^0-9]/", "", $cpf);

        // Verifica se possui cnpj
        if (!empty($cnpj)) {
            // Armazena o cnpj e limpa o mesmo
            $this->user['company_document'] = preg_replace("/[^0-9]/", "", $cnpj);
        }

        // Verifica se possui IE
        if (!empty($ie)) {
            // Armazena o ie e limpa o mesmo
            $this->user['state_register'] = preg_replace("/[^0-9]/", "", $ie);
        }

    }


    /**
     * Método responsável por armazenar o endereco do usuario
     * a respeito.
     *
     * - Campos que devem ser informados no array:
     *
     *  - endereco
     *  - numero
     *  - bairro
     *  - cidade
     *  - cep
     *
     * @param array $endereco
     */
    public function setEndereco(array $endereco)
    {
        // Armazena os dados
        $this->user['address'] = $endereco["endereco"];
        $this->user['number'] = $endereco["numero"];
        $this->user['complement'] = $endereco["complemento"];
        $this->user['district'] = $endereco["bairro"];
        $this->user['city'] = $endereco["cidade"];

        // Limpa o cep
        $this->user['postal_code'] = preg_replace("/[^0-9]/", "", $endereco["cep"]);

        // Fixo como Brasil
        $this->user['country_id'] = "BR";
    }

    /**
     * Método responsável por retornar o objeto de
     * usuario preenchido e configurado para uso.
     *
     * @return stdClass
     */
    public function toArray()
    {
        return $this->user;
    }

}
