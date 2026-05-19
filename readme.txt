=== Produto 360 ===
Contributors: marceloviana
Tags: 360, viewer, product, 3d, rotation, woocommerce, elementor
Requires at least: 5.5
Tested up to: 6.7
Requires PHP: 7.2
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Visualizador de produtos em 360° com 36 imagens sequenciais. Suporta múltiplos produtos independentes via shortcode.

== Description ==

Plugin profissional para exibir produtos em rotação 360°. Cada produto é independente, com suas próprias 36 imagens e configurações.

**Recursos principais:**

* Interface administrativa intuitiva com Custom Post Type
* Upload de até 36 imagens por produto via Media Library
* Drag-and-drop para reordenar imagens
* Ordenação automática pelo nome do arquivo
* Pré-visualização em tempo real no painel
* Shortcode exclusivo para cada produto: `[produto360 id="produto-a"]`
* Autoplay, FPS, direção e cor configuráveis por produto
* Override de configurações via atributos do shortcode
* Suporte completo a desktop e mobile (drag + swipe + pinch zoom)
* Pré-carregamento otimizado de imagens
* Loader durante carregamento
* Compatível com Elementor, WooCommerce, Gutenberg e qualquer tema
* Não depende de serviços externos
* Múltiplos visualizadores na mesma página sem conflitos

== Installation ==

1. Faça upload do ZIP em **Plugins → Adicionar novo → Enviar plugin**
2. Ative o plugin
3. Acesse **Produtos 360°** no menu lateral do WordPress
4. Crie seu primeiro produto, adicione 36 imagens e copie o shortcode

== Usage ==

**Shortcode básico:**

`[produto360 id="meu-produto"]`

**Com opções:**

`[produto360 id="meu-produto" width="600px" height="600px" autoplay="no"]`

**Atributos disponíveis:**

* `id` (obrigatório) — slug do produto (recomendado) ou ID numérico
* `width` — largura máxima (padrão: 520px). Aceita px, %, em, rem, vw, vh
* `height` — altura fixa opcional (por padrão é quadrado 1:1)
* `autoplay` — yes / no (sobrescreve configuração do produto)
* `class` — classe CSS extra

**Configurações por produto (definidas no admin):**

* FPS (velocidade da rotação)
* Direção (horária / anti-horária)
* Cor dos controles

== Frequently Asked Questions ==

= Quantas imagens são necessárias? =

O ideal são 36 imagens (uma a cada 10°) para uma rotação suave. O plugin funciona com qualquer número entre 2 e 36+.

= Posso usar o mesmo plugin para vários produtos? =

Sim! Cada produto é totalmente independente, com seus próprios shortcode, imagens e configurações.

= Funciona em celular? =

Sim. Há suporte completo a swipe (1 dedo gira, 2 dedos fazem pinch zoom).

= Funciona com Elementor? =

Sim. Use o widget de Shortcode do Elementor e cole `[produto360 id="..."]`.

== Changelog ==

= 1.1.0 =
* Novo: zoom inicial configurável por produto (slider de 1.0x a 3.0x)
* Novo: escolha do sentido de rotação ao arrastar o mouse (natural ou invertido)
* Botão "Reset" agora volta ao zoom inicial configurado, não a 1.0x
* Configuração de direção do autoplay separada do sentido de arrasto

= 1.0.0 =
* Lançamento inicial
* Custom Post Type para produtos 360°
* Drag-and-drop de ordenação de imagens
* Pré-visualização administrativa
* Shortcode com múltiplos atributos
* Múltiplas instâncias na mesma página
* Suporte a mobile (swipe + pinch zoom)
