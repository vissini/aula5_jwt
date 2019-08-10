<?php

//Quando falamos de API temos uma grande dúvida/ problema que é a segurança, principalmente pelo fato de não mantermos estado
//No mundo de hoje, temos API para qualquer coisa.
//Outro problema é a autenticação de um usuário, e de um cliente.
//Para resolver isso podemos usar o OAUTH2, o qual é um protocolo de cominucação definido.
//Se quiser pode ser implementado, mas já aviso que é bem complexo. Mas para nossa sorte, o Laravel tem uma biblioteca oficial que já implementa isso.
//Nesse exemplo vamos usar em conjunto o JWT para gerar o token que vamos usar na nossa aplicação.
//Para verificar toda a especificação do o OAUTH2 pode consultar na IETF (RFC 6749)

//Resource Owner: O usuário
//Resource Server: a API
//Authorization Server: quase sempre é o mesmo servidor da API, mas poderiamos ter um servidor unico para autenticar todas as APIS.
//Client: os aplicativos de terceiros
//São partes muito bem definidas quando usamos o OAUTH2


//Como é na pática???
//Vamos imaginar que tenhamos um cliente....pode ser aplicação mobile web ou externa.
//Vamos ter nosso servidor OUTH2 e o nosso servidor API, que podem ser no mesmo servidor ou não.
//Nosso cliente faz uma requisição de autorização no OAUTH2 se validar vai retornar um token de acesso,
//Nosso cliente guarda esse token de acesso,e  qdo ele quiser consumir as informações da nossa API, ele vai a requisição e o token,
//Onde toda vez que tivermos uma requisição em nossa API ela valida o token no nosso servidor de autenticação OAUTH2.
//Se for valido, nossa API retorna o que foi solicitado na requisição, senão retornar um status 401
//O nome desse token para acessarmos a API é access token ou token de acesso, com um tempo de vida bem curto. Se esse token expirar,
//o cliente tem que solicitar para fazer um refresh token
//Temos também um token chamado de refresh token o qual tem uma validade bem maior
//E por fim podemos revogar o acesso de um token quando quisermos,
//Podemos também remover o acesso de um cliente.


//Então, tudo está lindo e maravilhoso, seguro??
//Não, não está, pois se esse token ficar exposto, e alguem tiver acesso a esse token, ele pode se conectar com a nossa API,
//Sendo assim o token deve ser passado no header na nossa requisição HTTP
//E devemos usar o protocolo HTTPS, pois o header é criptografado.

//Agora vamos começar a trabalhar usando o Laravel Passport

//configurar o SQLITE no .env
//Criar o arquivo
touch database\database.sqlite

//para ver se está funcionando, dar o artisan migrate.

//Agora vamos criar um seeder para poder popular alguns usuários
php artisan make:seeder  UsersTableSeeder


// Na nossa seeder criada adicionar o código no método run
factory(\App\User::class)->create([
  'email' => 'user1@user.com'
]);
factory(\App\User::class)->create([
  'email' => 'user2@user.com'
]);

//Descomentar a linha da nossa seeder de usuário no DatabaseSeeder
//Rodar o comando
php artisan db:seed

//Para verificar se tudo ficou certo
sqlite3 database\database.sqlite
select * from users;

//agora vamos criar nossa autenticação convencional
php artisan make:auth

//Rodar o server e verificar se tudo está funcionando

//Agora vamos integrar o Passaport
//Ver documentação
https://laravel.com/docs/5.5/passport

//Rodar comando para instalar
composer require paragonie/random_compat:2.*
composer require laravel/passport "4.0.*"

//Ir no modelo do Usuario e adicionar a Trait HasApiTokens a qual vai ser responsável por ligar os tokens ao usuário
use Laravel\Passport\HasApiTokens;

use HasApiTokens, Notifiable;

//Mostrar que adicionou novos comandos no php artisan para o Passaport
php artisan
//Temos o passaport client para gerar um novo client acess token
//O passaport install o comando que vamos executar para preparar o uso do passaport
//E temos o passportkeys, para gerar nova chave de criptografia
//Vamos rodar agora o comando
php artisan passport:install

//Guardar os tokens gerados

//Agora vamos verificar no nosso banco de bados
.tables
select * from oauth_clients;

//Toda vez que uma aplicação externa for acessar nossa API, ele vai ter que estar cadastrada nessa tabela.
//Ja temos os dois que foram gerados por padrão

//Agora precisamos criar nossas rotas
//Next, you should call the Passport::routes method within the boot method of your  AuthServiceProvider
use Laravel\Passport\Passport;

public function boot()
    {
        $this->registerPolicies();

        Passport::routes();
    }

//Rodar o comando
php artisan route:list
//E verificar as rotas

//Agora vamos adicionar o support frontend. esse é o diferencial que é a possibilidade de criar os clients, tokens, revogar acessos etc
//Gerar essa parte é bem tranquilo, para isso vamos trabalhar com webcomponents baseado no nosso vuejs, mas fique tranquilo, para isso nem precisaremos saber vuejs
//O legal é que vc vai colocar apenas tags personalisadas e já estara pronto

//Para isso precisamos dar um npm install
npm install

//Enquanto instala precisamos publicar nossos webcomponents, para isso rodar o comando:
php artisan vendor:publish --tag=passport-components

//Para verificar podemos ir em resources/assets/js/components
//Temos uma pasta passport com todos os components que vão nos ajudar a fazer a integração com o nosso webview

// Agora precisamos importar nossos components para o arquivo resources/assets/js/app.js
Vue.component(
  'passport-clients',
  require('./components/passport/Clients.vue')
);

Vue.component(
  'passport-authorized-clients',
  require('./components/passport/AuthorizedClients.vue')
);

Vue.component(
  'passport-personal-access-tokens',
  require('./components/passport/PersonalAccessTokens.vue')
);

//Agora precisamos rodar o comando abaixo para recompilar nossos JS
npm run dev

//Agora precisamos escolher uma área restrita para publicarmos esses components
//Vou usar a home para isso
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <passport-clients></passport-clients>
            <passport-authorized-clients></passport-authorized-clients>
            <passport-personal-access-tokens></passport-personal-access-tokens>
        </div>
    </div>
</div>
@endsection


//Agora podemos efetuar o login e ir em home para ver o que foi criado
//O terceiro componente ainda não apareceu porque não fizemos nenhum acesso de client

//Uma configuração que faltou foi definir o driver de autenticação para API usar o passaport
// para isso devemos abrir o arquivo config/auth.php
'api' => [
  'driver' => 'passport',
  'provider' => 'users',
],

//Agora vamos começar a testar nossa API, mas como assim, se não criamos nada ainda??
//Mas a gente já tem um endpoint para testar.
//Vamos abrir o arquivo de rotas da API
//Podemos verificar que já temos uma rota ali
//A qual é uma rota que retorna o usuário que está autenticado no momento.
//Se olhar bem na rota vemos que estamos chamando um middleware auth:api, e o que significa isso??
//Significa que estamos usando a autenticação baseada no driver API
//Então para isso só precisamos fazer uma requisição HTTP para testar se a nossa autenticação da API está funcionando.
//Podemos fazer isso via CURL, via Browser, gosto sempre de usar o postman
//Falar um pouco sobre o postman
//Vamos testar então
http://localhost:8000/api/user
//Verificar que retornou status 200, então será que deu certo??
//Não, na verdade o que ele retornou foi a tela de login, ou seja um html.
//Mas ele não deveria retornar o JSON, um XML
//Como a gente não passa nenhum header dizendo que gostariamos de receber a resposta em JSON, e não estamos autorizado, então o laravel redireciona para a tela de login,
//e retorna o HTML dessa tela.
// Para isso vamos usar um header chamado accept, que significa o formato que gostariamos de receber o retorno, mas não quer dizer que o servidor vai retornar nesse formato
//Mas isso vai depender da configuração do servidor
Accept : application/json

//Agora podemos verificar que ele retornou o status 401, que significa sem autorização de acesso
{"message":"Unauthenticated."}
//Logo se a gente recebeu essa mensagem significa que nosso oauth server está funcionando.

//Agora vamos começar a trabalhar com o Personal Acess
//Esse é um token de longa vida...algo em torno de 1 ano.
//Mas pode ser inseguro, pois se alguem conseguir o token vai conseguir acessar nosso programa.
//Outro problema se você tiver o PHP 32bites não vai funcionar, pois o timestamp gerado ultrapassado os 32bites
//Agora vamos criar nosso token

eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjY5NjBjYjc1NzUxMzg4M2MzYzEyZTc0MmU1ZTQ0MTBiMzQ5ZjYyMzgwZjVkZmRjZjFkNWQ4NTQyYzkyYmEwNjlmNTQ3OThlMTA2ZTU3OTE0In0.eyJhdWQiOiIxIiwianRpIjoiNjk2MGNiNzU3NTEzODgzYzNjMTJlNzQyZTVlNDQxMGIzNDlmNjIzODBmNWRmZGNmMWQ1ZDg1NDJjOTJiYTA2OWY1NDc5OGUxMDZlNTc5MTQiLCJpYXQiOjE1NjQwODQ1ODIsIm5iZiI6MTU2NDA4NDU4MiwiZXhwIjoxNTk1NzA2OTgyLCJzdWIiOiIxIiwic2NvcGVzIjpbXX0.UYb3IOkBEf54xvq1Q1CZtblRSCE7ZKstq1_Tb9_ptOi_twpgnVQsSm2rP9ZHPC19HKLDX8E1MKQdooGOjvVSh4SV8oAIpBX6_1K3UT2RGLUdOwyMCIvWEIVX9hBQQ67UVtOe2ovF3hWkzGlGnnap8OLXoNbABF4_KXhYmL16oIp1ZoHC3h17za9MnntS3qaK9p7JufVQfH8rFG-nFbos4HBsErvC_rfq7bfO7GsVDHqT9ashG86v7qcS7ySa00UBaesjY6tI-7ohvtkxSyUDa0m_aa-2CkJWMyNgQc4VesW0Gn8frY5XZLUuBZKaUsOuk7iltJvdvMchfNfzUfDfp5zCqeb7iInUugHGx9j4QJxDlrDhTVUhBl0AarXX2QXc9zQjZLcpltwf8TWwdwbYMpG429O7EVWe7KD_7C4-u16S57wrChB_bhDowDXsm__8xU-Tdhfa_2meLVS9r6wyoTNq3J1U0d_6iT_sPKyvYClFRmUSSmtC5SPHA1itOfsEgiEU4lFXv_xTh6mzXKfRav-U9sIYmUSELmJFeqsF74HDqHMXEft-iZN5YMe0xU1YEYfEIExtBLGsAH24vIdTEZK6EtnP1PsoY8TtMoE-2pT3swCeG-Rays4DecBjA7_Ik5RUDGYWF7Y-UM-JAMO5zKWGKbT2NHJBallAVHCRRU4

// Vamos verificar nosso token agora no jwt.io
//agora vamos verificar no banco aonde esse token é gerado
select * from oauth_access_tokens

//Agora vamos testar se com o token conseguimos retornar o nosso usuário logado,
//Lembrando que esse personal access token é sempre ligado a um usuário logado
Authorization: Bearer

http://localhost:8000/api/user

//Se criarmos um outro token para esse usuário conseguiremos acessar da mesma maneira, só a expiração que mudou
//Podemos também apagar o token para remover o acesso....

//Lembrando que é sempre utilizado para desenvolvimento, principalmente pelo seu longo tempo de duração

//Geralmente quando trabalhamos com OAUtH server vamos precisar criar um cliente para cada aplicação que for acessar nossa API.
//Sendo assim as pessoas que quiserem acessar nossa API precisaram de um login para acessar nossa área administrativa e criar seu cliente
//Agora vamos criar nosso cliente.
//Nome é apenas um identificador, mas não influência em nada
//Redirect URL é muito importante porque é aqui que o nosso OAUTH server vai retornar a resposta se permite ou não o acesso.
//Cadastrar com o callback
http://localhost:9999/callback
//Observe que ele criou o nosso client ID e o secrect, que são informações importantes para requerer nosso access token.
//Agora vamos configurar as rotas
//Considere que estaja sendo feito uma aplicação client que não é essa, apesar de estarmos fazendo aqui, para não precisar recriar e configurar toda a aplicação


//Quando o usuário acessar nossa URL / que seria nossa pagina principal então nós vamos requisitar o acesso do nosso OAUTH, através de uma URL especifica e alguns parametros.
//Para isso vamos criar nossa Rota em web.php para o /
$query = http_build_query([
  'client_id' => '3',
  'redirect_uri' => 'http://localhost:9999/callback',
  'response_type' => 'code',
  'scope' => ''
]);

dd($query);
//onde Client ID é o id do nosso client que criamos anteriormente
//redirect_url é o mesmo que cadastramos
//Vamos definir o tipo da resposta que queremos receber através do response_type, definindo o nome do parametro que vamos receber o código para requisitar o acesso
//e o Scope fica vazio.

//Lembrando que esta rota estamos simulando como se fosse de outro servidor, ou seja de uma aplicação cliente.
//Para rodarmos nosso servidor, podemos rodar o seguinte comando
php artisan serve --port=9999

//Então acessando a URL
http://localhost:9999
//Podemos verificar a URL criada para chamar nossa API

//Então agora que temos os parametros que devem ser passados, vamos chamar a rota da API responsável pela autorização do Client
$query = http_build_query([
  'client_id' => '3',
  'redirect_uri' => 'http://localhost:9999/callback',
  'response_type' => 'code',
  'scope' => ''
]);
return redirect("http://localhost:8000/oauth/authorize?$query");

//Se atualizarmos nossa pagina você vai verificar que ele vai redirecinar para a tela de login, isso ocorre porque para que a API solicite a autorização você precisa estar logado.
//Vamos então logar com o usuário2 que criamos e ver o que acontece.

//Podemos verificar que ele mostra uma tela que solicita a autorização, o que é uma das vantagens do passaporte, o que não acontecia com outras bibliotecas de terceiros,
//que trazia apenas o bakcend.

//Antes de clicarmos em Autorize, vamos criar nossa rota de callback
//Não vou nem criar um controller para isso
//Primeiro só para vermos o retorno do Request
use Illuminate\Http\Request;
Route::get('callback', function(Request $request){
  $code = $request->get('code');
  dd($code);
});

//Podemos verificar que ele gerou nosso código de autorização, que ainda não é nosso access token

//Podemos verificar esse código dando um select na tabela Oauth_auth_codes
sqlite3 database\database.sqlite
select * from oauth_auth_codes;

//Então vamos terminar nosso processo de autorização
// Vamos fazer uma requisição HTTP de dentro da nossa rota para fazer a requisição do nosso token.
//Para isso usamos uma biblioteca que já temos instalada chamada Guzzle que é uma das melhores para trabalhar com requisição http no php

Route::get('callback', function(Request $request){
  $code = $request->get('code');
   $http = new \GuzzleHttp\Client();
   $response = $http->post('http://localhost:8000/oauth/token', [
      'form_params' => [
          'client_id' => '3',
          'client_secret' => 'fzkGEe01LCOZm4aqMK2at9I8um3zJv25ZLgObn1x', //é o nosso secret, gerado qdo criamos o acesso
          'redirect_uri' => 'http://localhost:9999/callback', //Ele vai comparar, se for diferente retorna um erro
          'code' => $code, //O código retornado da solicitação de autorização
          'grant_type' => 'authorization_code' //O tipo de autorização que estamos trabalhando. Temos outros tipos (password, client etc)
      ]
   ]);
   dd(json_decode($response->getBody(), true));
});

//Agora vamos testar....Passa isso dar um voltar, atualizar a pagina e autorizar novamente

//E agora temos nosso access token, e o refresh token. em formato JWT
//Testar usando o exemplo do personal access tokem e alterando o token

//vamos testar o refresh token
//Vamos imaginar que nosso token expirou, e vamos testar via postman mesmo como funciona o refresh token
POST
http://localhost:8000/api/oauth/token

Body form-data
client_id = 3
client_secret = fzkGEe01LCOZm4aqMK2at9I8um3zJv25ZLgObn1x
scope =
refresh_token = def50200c894efbd69126b791e625f771925b34f74bc3a18cac949e8ea5b18fb5edfdac38c555a3cf1d24be78bb83fdec3d6ff1cb88fe63b047084d83ebfbdfe27de42fd8bb75ba8e734716c4a48c836728e58a90d4d9aa68f1c36454dd49e05b59e436dd666999e581adce63e8d2437a9fd23c7614efcc1460d89afd9f0c05db1d6a6f12f640f4e4cddf792a0d6b236742189e57025e4cd26bfa32a6ea65a746aecfb26cbfad09f637717afad4a1f9414abb29dbd522bf0fa4156c43c278b4809ebbe90ec166a772868071dffb8b3d1c682e5cd98ab6035eba8ec70cadf0b8316cc829a2cad9c4d078cd0e6340e50757036087fb5a13e948b712ebb68bdced8c73ade74bf2002e0724ef08e8b81d45bd2813d7456adf4f6b178482a02ce6ad8c7198916d59d9fcbecbb2da51d837b3b87d6a611bb69307353de1adee475b4862dfa9c4d012ce31c1d7cfe1af0f17ffa48774f14d3a4e43b91a9414c1bb49f17c9
grant_type= refresh_token

//E pronto, gerou nosso novo token. Então aqui temos um processo completo para geração do nosso token.
//Mas e se quisessemos mudar os tempos do nosso token como fariamos.
// Vamos lá em App/Providers/AuthProvider

// e adicioanmos o seguinte código
use Carbon\Carbon;
public function boot()
    {
        $this->registerPolicies();
        Passport::routes();
        Passport::tokensExpireIn(Carbon::now()->addYears(10));
        Passport::refreshTokensExpireIn(Carbon::now()->addYears(15));
    }

//Mas só lembrando que em uma aplicação real esse tempo é horas, ou até mesmo em minutos.
//Para testar pedimos uma nova autorização, e verificamos no banco
select * from oauth_refresh_tokens;
select * from oauth_access_tokens;
//Agora vamos ver tudo isso mas com um exemplo de um Crud para fixar melhor esses conceitos.






//Vamos criar uma aplicação Laravel nova, para poder fixar todo o nosso conhecimento, desta vez criando um CRUD
//Enquanto isso vamos criando nosso banco de dados, desta vez vamos usar mysql
//vAMOS CONfigurar nosso .env
//Adicionar
use Illuminate\Support\Facades\Schema;
Schema::defaultStringLength(191);

//Agora vamos criar nosso model Product
php artisan make:model Product -mc

//Na migration vamos adicionar os campos a tabela de produtos
Schema::create('products', function (Blueprint $table) {
  $table->increments('id');
  $table->string('title');
  $table->text('body');
  $table->timestamps();
});

//Criar a rota da API
Route::prefix('v1')->group(function () {
  Route::get('/products', 'productsController@index');
});

//Criar o metodo index no controller e retornar todos os dados
public function index()
    {
        return Product::all();
    }

//Pronto já temos nosso primeiro endpoint que já conseguimos consultar no browser ou no postman

//Agora vamos ver como fazer o insert
// Primeiro criar o método store no controller
public function store(Request $request)
    {
        return Product::create($request->all());
    }
//Agora criar a rota
Route::post('/products', 'productController@store');

//Agora adicionar o fillable no model
protected $fillable = ['title', 'body'];

//Agora vamos testar no postman
Método post
URL: http://localhost:8000/api/v1/products
Body: x-www-form-urlencoded
    title = Produto 1
    body: Desc Prod 1

//Verificar que ele retornou o registro inserido
//Rodar agora para get, para listar o registro Criado

//Agora vamos fazer a parte do edit
//Primeiro vamos criar a roda, lembrando que no edit é put
Route::put('/products/{product}', 'productController@update');

//Agora vamos criar o método no controller
public function update(Request $request, Product $product)
    {
        $product->update($request->all());
        return $product;
    }

//Agora vamos chamar no postman
Método PUT
URL: http://localhost:8000/api/v1/products/1
Body: x-www-form-urlencoded
    Desc = Produto 1 Atualizado

//Mostrar chamando o GET que atualizou o produto

//Agora vamos criar a parte de vizualização do produto
//Primeiro criar a rota
Route::get('/products/{product}', 'productController@show');

//Agora vamos criar o método
public function show(Product $product)
    {
        return $product;
    }

//e por fim testar no postman
Método GET
URL: http://localhost:8000/api/v1/products/1

//Agora vamos finalizar nosso Crud fazendo o DELETE
//Primeiro criar a rota
Route::delete('/products/{product}', 'productController@destroy');

//Criar método
public function destroy(Product $product)
    {
        $product->delete();
        return $product;
    }

//Testar no postman
Método DELETE
URL: http://localhost:8000/api/v1/products/1

//Ele vai retornar o registro apagado. Podemos chamar o index ou o show, para verificar que o registro foi apagado


//Agora vamos olhar nosso arquivo de rotas, temos vários endpoints para products, e para cada endpoint, temos uma roda,
//Pensando que nossa api vai ter produtos, categorias, usuarios, compras, devoluçoes, etc....iriamos ter um número muito grande de rotas,
//Nesse caso o que poderiamos fazer para reduzir esse número de rotas??
//Lembra da primeira aula, podemos usar o resource, inclusive se observar bem os métodos criados no controller já estão seguindo esse padrão.
//Então vamos alterar nossa rota.
Route::resource('products', 'productController');

// rodar o comando:
php artisan route:list
// e verificar que as rotas que tinhamos antes continuam sendo listadas.
// E temos uma forma simplificada para quando tivermos mais de um resource
Route::prefix('v1')->group(function () {
  Route::resources([
      'products' => 'productController',
      'users' => 'userController',
      ]);
});

//Agora que adicionamos o resource para usuários, vamos criar o controller para esse resource
php artisan make:controller userController -r

//Lembrando que o parametro -r no final faz com que o controller criado já seja do padrão resource
//Agora vamos adicionar todo o código no nosso controller;
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;

class userController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return User::all();
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        return User::create($request->all());
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(USer $user)
    {
        return $user;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $user->update($request->all());
        return $user;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(USer $user)
    {
        $user->delete();
        return $user;
    }
}


//Agora vamos testar no postman

//Agora vamos aproveitar e fazer a nossa validação aproveitando para relembrar.
//Para isso vamos criar um request
php artisan make:request ProductRequest

//Vamos abrir nosso request e adicionar as regras
// mudar para true no authorize
//Adicionar no array do rules
'title' => 'required',
'body' => 'required|min:10',

// Agora ir no nosso controller e mudar o request

//Depois disso vamos tentar adicionar um produto no postman sem passar nenhum parametro
// Verificamos que ele retornou para a pagina inicial do laravel, isso ocorre pq o retorno está do tipo html.
// Para resolver isso temos 2 opções
// 1º que seria chamar via ajax, o qual envia o header: (Podemos testar no postman)
X-Requested-With:XMLHttpRequest
//Ou então passar o header accept
Accept:application/json
//O qual está dizendo o tipo de resposta que estamos esperando



//Agora vamos instalar o passport para fazer a parte de autenticação da API
composer require paragonie/random_compat:2.*
composer require laravel/passport "4.0.*"
php artisan migrate
php artisan passport:install

// vamos guardar as chaves que ele gerou
Encryption keys generated successfully.
Personal access client created successfully.
Client ID: 1
Client Secret: pCB7EsJFTeDgMGd6pH5PHYjKGwbyQmsD7ao8SIHH
Password grant client created successfully.
Client ID: 2
Client Secret: vPIhTgNHeHEzsyIgkY2rTzTvLw29o3bWz4hwgxuE

//Agora vamos adicionar a trait apitokens no user model
use Laravel\Passport\HasApiTokens;

use HasApiTokens, Notifiable;

//Agora vamos ativar as rotas de autenticação, para isso vamos altera nosso authServiceProvider

use Laravel\Passport\Passport;

public function boot()
    {
        $this->registerPolicies();

        Passport::routes();
    }

//E agora a ultima coisa que vamos fazer é alterar os metodos de autenticação em config/auth
'api' => [
  'driver' => 'passport',
  'provider' => 'users',
],

//Para validar se nossa instalação deu certo podemos acessar a seguinte rota:
get http://localhost:8000/oauth/token
// Se retornar a mensagem abaixo, podemos entender que deu certo. Se precisar de algo a mais, iremos alterar depois
{"error":"unsupported_grant_type","message":"The authorization grant type is not supported by the authorization server.","hint":"Check the `grant_type` parameter"}


//Agora vamos trabalhar com o frontend para a geração dos nossos tokens de acesso, para isso primeira coisa que vamos fazer é:
// rodar o comando abaixo para criar os webcomponents
php artisan vendor:publish --tag=passport-components


// Agora precisamos importar nossos components para o arquivo resources/assets/js/app.js
Vue.component(
  'passport-clients',
  require('./components/passport/Clients.vue')
);

Vue.component(
  'passport-authorized-clients',
  require('./components/passport/AuthorizedClients.vue')
);

Vue.component(
  'passport-personal-access-tokens',
  require('./components/passport/PersonalAccessTokens.vue')
);

// agora vamos dar um npm install

//Enquanto instala podemos abrir um outro terminal e habilitar a autenticação na nossa aplicação
php artisan make:auth

//Agora vamos adicionar nossos webcomponents na nossa view do home.
<passport-clients></passport-clients>
<passport-authorized-clients></passport-authorized-clients>
<passport-personal-access-tokens></passport-personal-access-tokens>

//Depois do npm install ter terminado, precisamos atualizar nosso arquivo app.js da pasta public, para isso rodar o comando:
npm run dev

// Agora vamos acessar a pasta raiz, e como ainda não temos nenhum usuário vamos criar um;
// Podemos ver que já apareceu uma área para controlar os cadastros dos tokens

//Mas vamos testar agora, para isso vamos chamar a rota:
http://localhost:8000/oauth/token
//passando os parametros:
grant_type:password //Dizendo que nosso tipo de autenticação é via password
client_id: 1 //Vamos pegar o id que já foi gerado
client_secret: pCB7EsJFTeDgMGd6pH5PHYjKGwbyQmsD7ao8SIHH//Vamos pegar o Secret gerado para o id
username: diego@vissini.com.br
password:secret
scope:

//E vemos que temos uuma mensagem de client invalido, pois como estamos usando o grant type password, precisamos criar um client desse tipo
//Para isso rodar o comando:
php artisan passport:client --password

Password grant client created successfully.
Client ID: 3
Client Secret: WjtYy0VqkmUgQ8YTHl6gOYzKxkrtsnKFBnfE1VoU

//Vamos usar esses dados para testar e ver se gera nosso token de acesso
//Muito bem...token gerado

//Agora vamos proteger nossos dados da API
//Para isso vamos adicionar o middleware na nossa rota.
Route::middleware('auth:api')->prefix('v1')->group(function () {
  Route::resources([
      'products' => 'productController',
      'users' => 'userController',
      ]);
});

//Agora se tentarmos acessar a rota de listagem de produtos, verificamos que redirecionou para a tela de login,
//Mas se passarmos o header accept, vemos que ele retorna 401 unauthorized, ou seja sem autorização para acessar
//Então agora preciso me autenticar para poder acessar, e como eu faço isso??
//precisamos passar aquele token que geramos anteriormente
Authorization: Bearer token
//E pronto, agora temos nosso acesso

// Mas temos um detalhe nessa forma de autenticação, se você fosse utilizar essa APi no backend, onde ninguem teria acesso aos codigos da aplicação
// ninguem teria acesso a esse client_secret e não seria problema, mas se fosse utilizar no frontend ou em uma aplicação mobile, o ideal seriamos usar algo
//Para isso deveriamos criar conforme exemplo anterior Criando um oauth client, e tudo mais. Para não tomar muito tempo vamos usar esse primeiro método aqui.


//Agora vamos ver como podemos fazer para que um usuário/aplicação cliente só enxergue seus próprios dados.
//Para isso devemos criar uma policy. mas antes vamos adequar a tabela de produtos
// no migration de produto, adicionar o campo user_id
$table->integer('user_id')->unsigned();

// Agora devemos rodar o comando para atualizar o banco
php artisan migrate:refresh

//Agora vamos alterar nosso metodo create no modelo products para gravar o user_id
public function store(ProductRequest $request)
    {
        $data = $request->all();
        $data['user_id'] = \Auth::user()->id;
        return Product::create($data);
    }

//Adicionar o campo novo no fillable

//Como limpamos nosso banco ao executar o migration refresh, agora vamos criar 2 usuarios (via site)
//Também precisamos rodar o comando
php artisan passport:install

//Logar com o user2 e gerar um personal access token
//Agora com esse access token, vamos cadastrar um produto

//Logar com o user1 gerar seu personal access token, e tb criar um produto
//Agora vamos começar a criar nossa police de modo que as ações do usuário 1 sejam permitidas apenas para dados do usuário 1
php artisan make:policy ProductPolicy --model=Product

//Verificamos que dentro de app ele criou uma pasta policy, onde encontrasse nosso police...
//Cada metodo que está lá é referente a uma ação do resource, mas pode inserir outros se necessário.
//Ele trabalha sempre com o retorno de true ou false, onde true permite e false nega
//Vamos fazer o teste com o delete alterando o nosso metodo

public function delete(User $user, Product $product)
    {
        return $user->id === $product->user_id;
    }

//Como que eu uso agora?? antes de usar, precisamos dizer que queremos usar essa policy
// Vamos alterar nosso serviceProvider então
protected $policies = [
  'App\Product' => 'App\Policies\ProductPolicy',
];

//Agora é só ir no método do controller do produto e adicionar para usar esse policy
//No caso vamos testar em destroy
public function destroy(Product $product)
    {
        $this->authorize('delete', $product);
        $product->delete();
        return $product;
    }

//Agora vamos tentar remover um registro do User 2 estando autenticado como user 1 e você vai ver que vai retornar uma mensagem:
message": "This action is unauthorized., com status code - 403

//Agora vamos tentar deletar o registro do user 1, e pronto, registro deletado.

//Agora para finalizar nossa api antes de liberar ela para o mundo, vamos ver a parte de cache.
//Voce vai usar um facade cache no metodo index do controller products
public function index()
    {
        $minutes = \Carbon\Carbon::now()->addMinutes(10);
        $products = \Cache::remember('api::products', $minutes, function () {
            return Product::all();//Quando ocache não existir ou for invalido
        });
        return $products;
    }
//Agora vamos testar....
//primeiro manda listar todos os registros.
//Agora vamos inserir um produto novo
//rodar novamente a lista de produtos, e ver que esse registro novo ainda não apareceu, mas mostrar que ele está no banco
//Para zerar o cache é bem facil, vamos demonstrar simulando que sempre ao inserir um novo produto, devemos limpar o cache
public function store(Request $request)
    {
        \Cache::forget('api::products');
        $data = $request->all();
        $data['user_id'] = \Auth::user()->id;
        return Product::create($data);
    }

//Agora vamos inserir um novo produto, e mostrar que ele vai aparecer na listagem
//Por padrão ele grava esse cache em arquivo, mas você pode mudar isso no .env
//As opções podem ser mencache, redis (que é o mais usado);

//Agra vou demonstrar um problema que temos na nossa API
//para isso vamos criar um arquivo na pasta public
teste.html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>

    <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
    <script>
        $.get('http://localhost:8000/api/cors_example');
    </script>
</body>
</html>

//Vamos criar uma rota para testar
Route::get('cors_example', function(){
  return ['status'=>'OK'];
});

//Agora se eu tentar abrir essa rota, vai dar tudo certo,
//Mas lembrando que é uma API e provavelemente ela vai ser chamada de outros servidores.
//Vamos simular isso
php artisan serve --port=9999

//E vamos abrir a rota, vamos verificar que vai dar um erro agora, isso acontece pq o proprio browser faz esse bloqueio quando o acesso é de outro servidor
http://localhost:9999/teste.html

//No laravel a forma mais facil de resilver isso é:
composer require barryvdh/laravel-cors

//Primeira forma é fazendo essa liberação de forma global
//Alterar o arquivo app/kernel.php
protected $middleware = [
  \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
  \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
  \App\Http\Middleware\TrimStrings::class,
  \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
  \App\Http\Middleware\TrustProxies::class,
  \Barryvdh\Cors\HandleCors::class,
];

//Se testarmos agora....podemos ver que sumiu o erro.
//Outra forma é:
//Desfazer do middleware e mover pro API
'api' => [
  'throttle:60,1',
  'bindings',
  \Barryvdh\Cors\HandleCors::class,
],

//Outra forma é adicionar em routemiddleware, o que vai nos permitir setar o middleware na rota que quisermos testar
// ex:
protected $routeMiddleware = [
  'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
  'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
  'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
  'can' => \Illuminate\Auth\Middleware\Authorize::class,
  'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
  'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
  'cors' => \Barryvdh\Cors\HandleCors::class,
];

//Alterar a rota para usar esse middleware
//Costumo sempre deixar em API, para não ter perigo de liberar em alguma aplicação web que tenha
//Podemos também gerar o arquivo de configuração do cors
php artisan vendor:publish --provider="Barryvdh\Cors\ServiceProvider"
//E pronto...sua API está pronta para uso










//Agora vamos ver como criar uma API usando JWT para autenticação.

Json Web Token
jwt.io
//Abrir a pagina para explicar sobre o JWt, e dizer o qto é facil usar ele, não precisa de nenhuma tabela no banco


//Exemplo PHP puro

//header.payload.signature
//header
$header = [
    'alg' => 'HS256', //HMAC - SHA256
    'typ' => 'JWT'
];
$header_json = json_encode($header);
$header_base64 = base64_encode($header_json);
echo "Cabecalho JSON: $header_json";
echo "\n";
echo "Cabecalho JWT: $header_base64";
$payload = [
    'first_name' => 'Luiz',
    'last_name' => 'Diniz',
    'email' => 'argentinaluiz@gmail.com',
    'exp' => (new \DateTime())->getTimestamp()
];
echo "\n\n";
$payload_json = json_encode($payload);
$payload_base64 = base64_encode($payload_json);
echo "Payload JSON: $payload_json";
echo "\n";
echo "Payload JWT: $payload_base64";
$key = '7869876sfgsjhkgsdfkjhg868976xzvczx1111';
echo "\n\n";
$signature = hash_hmac('sha256', "$header_base64.$payload_base64", $key, true);
$signature_base64 = base64_encode($signature);
echo "Signature RAW: $signature";
echo "\n";
echo "Signature JWT: $signature_base64";
$token = "$header_base64.$payload_base64.$signature_base64";
echo "\n\n";
echo "TOKEN: $token";

//header tem 2 informaçoes
alg //criptografia
typ //Que é o tipo....vamos usar o JWT, mas temos outras variações

o Json dessas informações é convertido para base64


//payload
//significa corpo de dados
contem as informações sobre o token
contem validade
nome
email
id do usuario
ou qualquer outra informação não sensivel

signature
Ele vai criar uma assinatura, baseado no header e payload, em conjunto com a chave e o tipo de criptografica escolhido


//Verificar se vai falar Oauth X JWT
Oauth é um protocolo bem mais complexo, geralmente usado para autenticar aplicações


//Agora vamos começar a pensar em como integrar o JWT com o Laravel, para isso vamos usar uma biblioteca já pronta,
//Não temos a ncessidade de reinventar a roda
//vamos instalar a biblioteca jwt-auth
//Essa versão foi a que achei compativel com o Laravel 5.5
composer require tymon/jwt-auth:dev-develop#f72b8eb0deff2c002d40a8b0411a546c28ebec98

//depois de instalado precisamos mudar o config de auth, para usar jwt na api
//Antes de gerar nosso primeiro token, precisamos criar alguns usuários, para isso vamos criar uma seeder
//Nesse caso vou utilizar sqlite
touch database\database.sqlite

//Configurar o .env

//Criar a seeder usuário
php artisan make:seeder UsersTableSeeder

//Abrir a seeder e adicionar
factory(\App\User::class,1)->create([
    'email' => 'admin@user.com.br',
]);

//Descomentar databaseSeerder
rodar:
php artisan migrate
php artisan db:seed

//Agora como fazermos para integrar com o JWT
//Temos algo no token, que é o sujeito, o dono do token, no caso o proprio usuário

//Alterar modelo JWT adicionando o implements e 2 métodos
<?php

namespace App;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}

//Agora vamos gerar nossa chave....já temos um comando para isso
php artisan jwt:secret

// Essa chave na verdade será uma nova variavel no .env

// vamos acessar o tinker para ver algumas formar de gerar o token
// primeira dela é;
$user = \App\User::find(1);
\Auth::guard('api')->login($user);

//Devemos pegar esse token e jogar no jwt.io e ver as informações dele


//Uma outra forma de gerar, é como se fosse fazer o login
\JWTAuth::attempt(['email'=>'admin@user.com.br', 'password'=>'secret']);

//Agora vamos criar uma rota no laravel para que ao usuário passar a senha e usuário ele gere o token.
// primeiro criar um novo controller
php artisan make:controller Api\AuthController

<?php

namespace App\Http\Controllers\Api;

use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
class AuthController extends Controller
{
    use AuthenticatesUsers;
    public function login(Request $request)
    {
        $this->validateLogin($request);
        $credentials = $this->credentials($request);
        $token = \JWTAuth::attempt($credentials);
        return $this->responseToken($token);
    }
    private function responseToken($token)
    {
        return $token ? ['token' => $token] :
            response()->json([
                'error' => \Lang::get('auth.failed')
            ], 400);
    }

}
//Agora definir a rota de login
Route::name('api.login')->post('login', 'AuthController@login');

//Abrir o postman para testar
//Fazer testes com usuário invalido, e por fim com os dados correto do usuário e verificar que vai gerar um token


//Agora vamos verificar como fazer para liberar algumas áreas da api apenas para usuários autenticados
get http://localhost:8000/api/user
header accept application/json

//Verificar que deu 401 unauthorized
//Agora vamos passar o token
//e pronto...mostrou meu usuário


//Como revogar o token
// Para fazer isso, vou chamar de logout apenas para referenciar como funciona na aplicação web.
//Adicionar metodo no controller
public function logout(){
    \Auth::guard('api')->logout();
    return response()->json([],204); //No-content
}

//Adicionar a rota
Route::post('logout', 'Api\AuthController@logout');
//Deve ficar dentro da área protegida.

// Chamar no postman, passando o token
post http://localhost:8000/api/logout

//depois tentar listar os usuários para ver que revogou
get http://localhost:8000/api/user

//Agora vamos ver como definir o tempo de expiração dos tokens
// para isso vamos gerar o arquivo de configuração
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"

//Mostrar arquivo e falar sobre ele

//Mostrar o refresh token
// Criar metodo no controller
public function refresh(){
    $token = \Auth::guard('api')->refresh();
    return ['token' => $token]; //No-content
}

//Criar rota...pode ser fora da validação
Route::post('refresh', 'Api\AuthController@refresh');

//Refresh token nada mais é do que renovar o token sem precisar passar usuário e senha

//Podemos também configurar o refreshtoken de forma automática
// Como esse processo pode ser um pouco burocratico, podemos fazer este mimo para aplicação client
// para isso devemos ir em kernel e alterar:
use Tymon\JWTAuth\Http\Middleware\RefreshToken;

protected $routeMiddleware = [
    'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
    'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
    'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
    'can' => \Illuminate\Auth\Middleware\Authorize::class,
    'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
    'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
    'jwt.refresh' => RefreshToken::class
];

//Alterar o route group para usar esse middleware
Route::group(['middleware' => ['auth:api','jwt.refresh']], function(){
    Route::get('users', function(){
        return \App\User::all();
    });
    Route::post('logout', 'Api\AuthController@logout');
    //Route::resource('clients', 'ClientController', ['except' => ['create', 'edit']]);
});

// Mostrar no postman consultando os usuários o que acontece
//Agora a cada requisição ele gera um novo token no header

// Mas isso pode ser um problema, vamos dizer que vc faz muitas requisições ao mesmo tempo
//PAra resolver isso, damos um tempo extra para o token antigo expirar
//Vamos em config/jwt e alteramos$this->secret('What is the password?');
blacklist_grace_period
Posso jogar ela no .env e passar 30 que seria 30 segundos

//E mostrar que vc vai conseguir consultar até expirar o tempo.
