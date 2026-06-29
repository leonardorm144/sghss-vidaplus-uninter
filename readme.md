# SGHSS VidaPlus

Sistema de Gestão Hospitalar e de Serviços de Saúde desenvolvido como Projeto Multidisciplinar do curso de Análise e Desenvolvimento de Sistemas - UNINTER.

## Objetivo

O sistema tem como objetivo centralizar processos administrativos, clínicos e hospitalares, incluindo pacientes, profissionais, consultas, exames, prontuários, prescrições, leitos, internações, suprimentos, relatórios e auditoria.

## Tecnologias utilizadas

- PHP
- MySQL
- PDO
- HTML5
- CSS3
- JavaScript
- Chart.js

## Módulos implementados

- Login e controle de acesso por perfil
- Dashboard administrativo
- Pacientes
- Acesso do paciente
- Profissionais
- Consultas presenciais e telemedicina
- Exames
- Prontuários
- Prescrições digitais
- Unidades
- Leitos
- Internações
- Suprimentos hospitalares
- Relatórios
- Auditoria
- Interface responsiva

## Perfis de acesso

- Administrador
- Recepção
- Profissional
- Paciente

## Banco de dados

A estrutura do banco está disponível na pasta `database`.

Arquivos:

- `estrutura.sql`: contém a estrutura das tabelas do sistema.

Por segurança, dados reais, credenciais de banco e informações sensíveis não foram incluídos no repositório.

Para configurar o projeto localmente:

1. Criar um banco MySQL.
2. Importar `database/estrutura.sql`.
3. Copiar `config/db.example.php` para `config/db.php`.
4. Preencher os dados de conexão do banco.


## Sistema publicado

https://sghssuninter.free.nf/login

## Repositório

https://github.com/leonardorm144/sghss-vidaplus-uninter

## Observação

Este projeto foi desenvolvido para fins acadêmicos.
