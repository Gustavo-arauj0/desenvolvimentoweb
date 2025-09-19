<?php
// classes/Item.php - Entidade Item

class Item {
    private $id;
    private $usuario_id;
    private $categoria_id;
    private $titulo;
    private $descricao;
    private $condicao;
    private $imagem_url;
    private $status;
    private $data_cadastro;
    private $data_atualizacao;
    
    // Getters
    public function getId() { return $this->id; }
    public function getUsuarioId() { return $this->usuario_id; }
    public function getCategoriaId() { return $this->categoria_id; }
    public function getTitulo() { return $this->titulo; }
    public function getDescricao() { return $this->descricao; }
    public function getCondicao() { return $this->condicao; }
    public function getImagemUrl() { return $this->imagem_url; }
    public function getStatus() { return $this->status; }
    public function getDataCadastro() { return $this->data_cadastro; }
    public function getDataAtualizacao() { return $this->data_atualizacao; }
    
    // Setters
    public function setId($id) { $this->id = $id; }
    public function setUsuarioId($usuario_id) { $this->usuario_id = $usuario_id; }
    public function setCategoriaId($categoria_id) { $this->categoria_id = $categoria_id; }
    public function setTitulo($titulo) { $this->titulo = $titulo; }
    public function setDescricao($descricao) { $this->descricao = $descricao; }
    public function setCondicao($condicao) { $this->condicao = $condicao; }
    public function setImagemUrl($imagem_url) { $this->imagem_url = $imagem_url; }
    public function setStatus($status) { $this->status = $status; }
    public function setDataCadastro($data) { $this->data_cadastro = $data; }
    public function setDataAtualizacao($data) { $this->data_atualizacao = $data; }
    
    /**
     * Converte o objeto para array
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'usuario_id' => $this->usuario_id,
            'categoria_id' => $this->categoria_id,
            'titulo' => $this->titulo,
            'descricao' => $this->descricao,
            'condicao' => $this->condicao,
            'imagem_url' => $this->imagem_url,
            'status' => $this->status,
            'data_cadastro' => $this->data_cadastro,
            'data_atualizacao' => $this->data_atualizacao
        ];
    }
    
    /**
     * Cria um objeto Item a partir de array
     */
    public static function fromArray($data) {
        $item = new self();
        
        if (isset($data['id'])) $item->setId($data['id']);
        if (isset($data['usuario_id'])) $item->setUsuarioId($data['usuario_id']);
        if (isset($data['categoria_id'])) $item->setCategoriaId($data['categoria_id']);
        if (isset($data['titulo'])) $item->setTitulo($data['titulo']);
        if (isset($data['descricao'])) $item->setDescricao($data['descricao']);
        if (isset($data['condicao'])) $item->setCondicao($data['condicao']);
        if (isset($data['imagem_url'])) $item->setImagemUrl($data['imagem_url']);
        if (isset($data['status'])) $item->setStatus($data['status']);
        if (isset($data['data_cadastro'])) $item->setDataCadastro($data['data_cadastro']);
        if (isset($data['data_atualizacao'])) $item->setDataAtualizacao($data['data_atualizacao']);
        
        return $item;
    }
}