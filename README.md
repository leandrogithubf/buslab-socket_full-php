Buslab v3

# Docker Configurações

## Servidor Produção ou UHML
Para rodar este projeto no servidor de produção ou UHML, utilizar o Dockerfile contido na pasta .container.

**importante:** 
- Pasta '.container': O arquivo Dockerfile uma variável ambiente chamada **$SITE**, este variável deve conter o domínio da aplicação. Ex: api.buslab.com.br;
- Pasta '.container': O arquivo Dockerfile uma variável ambiente chamada **$FOLDER**, este variável deve conter o caminho da pasta raiz até a pasta que contém o dockerfile da produção;

O mesmo dito para produção se adequa para o Dockerfile de desenvolvimento.

## Servidor Localhost
Para rodar este projeto no servidor local (locahost), deve-se copiar o arquivo Dockerfile e .dockerignore contidos na pasta .container para a pasta raiz do projeto. Neste caso as variaveis ambientes estarão contidas nos arquivos .env.

Não será necessário alterar nada no Dockerfile para rodar localmente.

**importante:** O sistema está configurado para acessar o banco de dados da UHML, portanto caso vá usá-lo, deve-se ligar a VPN.

<br>

# Configurações Antigas (Digital Ocean)

##Socket de tratamento de checkpoints em tempo real

1. Criar arquivo de configuração .env
```
cp .env.dist .env
```

2. Rodar os listeners
```
php bin/listener-database
php bin/listener-filesystem
php bin/listener-realtime
php bin/listener-alert
```
