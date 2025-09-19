<?php

// classes/User.php - Entidade Usuário
class User {
    private $id;
    public $nome;
    public $email;
    private $senha;
    private $telefone;
    private $localizacao;
    public $tipo_usuario;
    private $ativo;
    private $data_cadastro;
    private $data_atualizacao;
    
    // Getters
    public function getId() { return $this->id; }
    public function getNome() { return $this->nome; }
    public function getEmail() { return $this->email; }
    public function getSenha() { return $this->senha; }
    public function getTelefone() { return $this->telefone; }
    public function getLocalizacao() { return $this->localizacao; }
    public function getTipoUsuario() { return $this->tipo_usuario; }
    public function isAtivo() { return $this->ativo; }
    public function getDataCadastro() { return $this->data_cadastro; }
    public function getDataAtualizacao() { return $this->data_atualizacao; }
    
    // Setters
    public function setId($id) { $this->id = $id; }
    public function setNome($nome) { $this->nome = $nome; }
    public function setEmail($email) { $this->email = $email; }
    public function setSenha($senha) { $this->senha = $senha; }
    public function setTelefone($telefone) { $this->telefone = $telefone; }
    public function setLocalizacao($localizacao) { $this->localizacao = $localizacao; }
    public function setTipoUsuario($tipo) { $this->tipo_usuario = $tipo; }
    public function setAtivo($ativo) { $this->ativo = $ativo; }
    public function setDataCadastro($data) { $this->data_cadastro = $data; }
    public function setDataAtualizacao($data) { $this->data_atualizacao = $data; }
    
    /**
     * Verifica se o usuário é administrador
     */
    public function isAdmin() {
        return $this->tipo_usuario === 'admin';
    }
    
    /**
     * Converte o objeto para array
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'email' => $this->email,
            'telefone' => $this->telefone,
            'localizacao' => $this->localizacao,
            'tipo_usuario' => $this->tipo_usuario,
            'ativo' => $this->ativo,
            'data_cadastro' => $this->data_cadastro,
            'data_atualizacao' => $this->data_atualizacao
        ];
    }
    
    /**
     * Cria um objeto User a partir de array
     */
    public static function fromArray($data) {
        $user = new self();
        
        if (isset($data['id'])) $user->setId($data['id']);
        if (isset($data['nome'])) $user->setNome($data['nome']);
        if (isset($data['email'])) $user->setEmail($data['email']);
        if (isset($data['senha'])) $user->setSenha($data['senha']);
        if (isset($data['telefone'])) $user->setTelefone($data['telefone']);
        if (isset($data['localizacao'])) $user->setLocalizacao($data['localizacao']);
        if (isset($data['tipo_usuario'])) $user->setTipoUsuario($data['tipo_usuario']);
        if (isset($data['ativo'])) $user->setAtivo($data['ativo']);
        if (isset($data['data_cadastro'])) $user->setDataCadastro($data['data_cadastro']);
        if (isset($data['data_atualizacao'])) $user->setDataAtualizacao($data['data_atualizacao']);
        
        return $user;
    }
}