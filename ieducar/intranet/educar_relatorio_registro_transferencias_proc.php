<?php

/**
 * i-Educar - Sistema de gest�o escolar
 *
 * Copyright (C) 2006  Prefeitura Municipal de Itaja�
 *                     <ctima@itajai.sc.gov.br>
 *
 * Este programa � software livre; voc� pode redistribu�-lo e/ou modific�-lo
 * sob os termos da Licen�a P�blica Geral GNU conforme publicada pela Free
 * Software Foundation; tanto a vers�o 2 da Licen�a, como (a seu crit�rio)
 * qualquer vers�o posterior.
 *
 * Este programa � distribu��do na expectativa de que seja �til, por�m, SEM
 * NENHUMA GARANTIA; nem mesmo a garantia impl��cita de COMERCIABILIDADE OU
 * ADEQUA��O A UMA FINALIDADE ESPEC�FICA. Consulte a Licen�a P�blica Geral
 * do GNU para mais detalhes.
 *
 * Voc� deve ter recebido uma c�pia da Licen�a P�blica Geral do GNU junto
 * com este programa; se n�o, escreva para a Free Software Foundation, Inc., no
 * endere�o 59 Temple Street, Suite 330, Boston, MA 02111-1307 USA.
 *
 * @author      Prefeitura Municipal de Itaja� <ctima@itajai.sc.gov.br>
 * @license     http://creativecommons.org/licenses/GPL/2.0/legalcode.pt  CC GNU GPL
 * @package     Core
 * @subpackage  pmieducar
 * @subpackage  Matricula
 * @subpackage  SolicitacaoTransferencia
 * @subpackage  Relatorio
 * @since       Arquivo dispon�vel desde a vers�o 1.0.0
 * @version     $Id$
 */

require_once 'include/clsBase.inc.php';
require_once 'include/clsCadastro.inc.php';
require_once 'include/clsBanco.inc.php';
require_once 'include/pmieducar/geral.inc.php';
require_once 'include/clsPDF.inc.php';


class clsIndexBase extends clsBase
{
  function Formular()
  {
    $this->SetTitulo($this->_instituicao . ' i-Educar - Registro de Matr&iacute;culas');
    $this->processoAp = '916';
    $this->renderMenu = FALSE;
    $this->renderMenuSuspenso = FALSE;
  }
}

class indice extends clsCadastro
{
  /**
   * Refer�ncia a usu�rio da sess�o.
   * @var int
   */
  var $pessoa_logada;

  var $ref_cod_instituicao;
  var $ref_cod_escola;
  var $ref_cod_serie;
  var $ref_cod_curso;
  var $ano;
  var $nm_escola;
  var $nm_instituicao;
  var $nm_curso;

  var $pdf;
  var $page_y = 139;
  var $get_link;
  var $campo_assinatura;
  var $total = 0;

  var $meses_do_ano = array(
    '1'  => 'JANEIRO',
    '2'  => 'FEVEREIRO',
    '3'  => 'MAR�O',
    '4'  => 'ABRIL',
    '5'  => 'MAIO',
    '6'  => 'JUNHO',
    '7'  => 'JULHO',
    '8'  => 'AGOSTO',
    '9'  => 'SETEMBRO',
    '10' => 'OUTUBRO',
    '11' => 'NOVEMBRO',
    '12' => 'DEZEMBRO'
  );

  function renderHTML()
  {
    $this->ref_cod_instituicao = $_POST['ref_cod_instituicao'];
    $this->ref_cod_escola      = $_POST['ref_cod_escola'];
    $this->ano                 = $_POST['ano'];

    $fonte    = 'arial';
    $corTexto = '#000000';

    if (is_numeric($this->ref_cod_instituicao) && is_numeric($this->ref_cod_escola)) {
      $obj_instituicao = new clsPmieducarInstituicao($this->ref_cod_instituicao);
      $det_instituicao = $obj_instituicao->detalhe();
      $this->nm_instituicao = $det_instituicao["nm_instituicao"];

      $sql = "
        SELECT
          fantasia as nome
        FROM
          pmieducar.escola,
          cadastro.juridica
        WHERE
          ref_cod_instituicao = {$this->ref_cod_instituicao}
          AND idpes = ref_idpes
          AND cod_escola = {$this->ref_cod_escola}
          AND ativo = 1
        UNION
        SELECT
          nm_escola
        FROM
          pmieducar.escola,
          pmieducar.escola_complemento
        WHERE
          ref_cod_instituicao = {$this->ref_cod_instituicao}
          AND cod_escola = ref_cod_escola
          AND cod_escola = {$this->ref_cod_escola}
          AND escola.ativo = 1";

      $db = new clsBanco();
      $this->nm_escola = $db->CampoUnico($sql);

      $sql = "
        SELECT
          cod_matricula,
          m.ref_cod_aluno,
          (SELECT
             nome
           FROM
             cadastro.pessoa p, pmieducar.aluno a
           WHERE
             a.cod_aluno = m.ref_cod_aluno AND a.ref_idpes = p.idpes)
          as nome_aluno,
          to_char(data_transferencia,'DD/MM/YYYY') as dt_transferencia,
          s.nm_serie,
          t.nm_turma,
          ts.ref_cod_matricula_entrada
        FROM
          pmieducar.matricula m,
          pmieducar.matricula_turma mt,
          pmieducar.turma t,
          pmieducar.serie s,
          pmieducar.transferencia_solicitacao ts
        WHERE
          m.ref_ref_cod_escola = {$this->ref_cod_escola}
          AND ref_cod_matricula_saida = cod_matricula
          AND ts.ativo = 1
          AND mt.ref_cod_matricula = m.cod_matricula
          AND mt.ref_cod_turma = t.cod_turma
          AND t.ref_ref_cod_serie = s.cod_serie
          AND m.ano = {$this->ano}
        ORDER BY
          nm_turma,
          nm_serie,
          dt_transferencia";

      $db->Consulta($sql);

      if ($db->Num_Linhas()) {
        $dados = array();

        while ($db->ProximoRegistro()) {
          $dados[] = $db->Tupla();
          $this->total++;
        }

        $this->pdf = new clsPDF("Registro de Matr�culas - {$this->ano}", "Registro de Matr�culas", "A4", "", false, false);
        $obj_instituicao = new clsPmieducarInstituicao();

        $this->pdf->largura  = 842.0;
        $this->pdf->altura   = 595.0;

        $this->page_y = 125;

        $this->pdf->OpenFile();

        $this->addCabecalho();

        $esquerda  = 30;
        $altura    = 130 + 18 * 2;
        $direita   = 782;
        $tam_texto = 8;
        $altura    = 130;

        $altura_escrita = 3;
        foreach ($dados as $dado_transferencia) {
          list($cod_matricula, $ref_cod_aluno, $nome_aluno, $dt_transferencia,
            $nm_serie, $nm_turma, $ref_cod_matricula_entrada) = $dado_transferencia;

          $this->pdf->linha_relativa($esquerda, $altura += 18, 0, 18);
          $this->pdf->linha_relativa($esquerda, $altura, $direita, 0);
          $this->pdf->escreve_relativo($cod_matricula, $esquerda + 3,
            $altura + $altura_escrita, 55, 30, $fonte, $tam_texto, $corTexto, 'center');

          $this->pdf->linha_relativa($esquerda + 55, $altura, 0, 18);
          $this->pdf->escreve_relativo($nome_aluno, $esquerda + 58,
            $altura + $altura_escrita, 300, 30, $fonte, $tam_texto);

          $this->pdf->linha_relativa($esquerda + 317 - 18, $altura, 0, 18);
          $this->pdf->escreve_relativo($dt_transferencia, $esquerda + 320 - 18,
            $altura + $altura_escrita, 150, 30, $fonte, $tam_texto, $corTexto);

          $this->pdf->linha_relativa($esquerda + 365-11, $altura, 0, 18);
          $this->pdf->escreve_relativo($nm_serie, $esquerda + 365 - 9,
            $altura + $altura_escrita, 72, 30, $fonte, $tam_texto, $corTexto, 'center');

          $this->pdf->linha_relativa($esquerda + 410+19, $altura, 0, 18);
          $this->pdf->escreve_relativo($nm_turma, $esquerda + 408 + 10 + 13,
            $altura + $altura_escrita, 35, 30, $fonte, $tam_texto, $corTexto, 'center');

          $this->pdf->linha_relativa($esquerda + 449 + 34, $altura, 0, 18);

          $estabelecimento_destino = NULL;
          if (is_numeric($ref_cod_matricula_entrada)) {
            $estabelecimento_destino = $this->getNomeEscola($ref_cod_matricula_entrada);
          }
          else {
            $this->pdf->escreve_relativo('Escola Externa ao Sistema', $esquerda + 452 + 34,
              $altura + $altura_escrita, 300, 30, $fonte, $tam_texto);
          }

          if (!empty($estabelecimento_destino)) {
            $this->pdf->escreve_relativo($estabelecimento_destino, $esquerda + 452 + 34,
              $altura + $altura_escrita, 300, 30, $fonte, $tam_texto);
          }

          $this->pdf->linha_relativa($esquerda + 757, $altura, 0, 18);

          $this->pdf->escreve_relativo(empty($estabelecimento_destino) ? '' : 'SC',
            $esquerda + 763, $altura + $altura_escrita, 50, 30, $fonte, $tam_texto);

          $this->pdf->linha_relativa($esquerda + 782, $altura, 0, 18);
          $this->pdf->linha_relativa($esquerda, $altura, $direita, 0);
          $this->pdf->linha_relativa($esquerda, $altura + 18, $direita, 0);

          if ($altura > $this->pdf->altura - 50) {
            $this->pdf->ClosePage();
            $this->pdf->OpenPage();
            $this->addCabecalho();
            $esquerda  = 30;
            $altura    = 130 + 18 * 2;
            $direita   = 782;
            $tam_texto = 8;
            $altura    = 130;

            $altura_escrita = 5;
          }
        }

        if ($altura > $this->pdf->altura - 50) {
          $this->pdf->ClosePage();
          $this->pdf->OpenPage();
          $this->addCabecalho();
          $esquerda  = 30;
          $altura    = 130 + 18 * 2;
          $direita   = 782;
          $tam_texto = 8;
          $altura    = 130;

          $altura_escrita = 5;
        }

        $this->pdf->CloseFile();
        $this->get_link = $this->pdf->GetLink();

        echo "<script>window.onload=function(){parent.EscondeDiv('LoadImprimir');window.location='download.php?filename=".$this->get_link."'}</script>";

        echo "
          <html>
            <center>
              Se o download n�o iniciar automaticamente <br />
              <a target='_blank' href='" . $this->get_link  . "' style='font-size: 16px; color: #000000; text-decoration: underline;'>clique aqui!</a><br><br>
              <span style='font-size: 10px;'>
                Para visualizar os arquivos PDF, � necess�rio instalar o Adobe Acrobat Reader.<br>
                Clique na Imagem para Baixar o instalador<br><br>
                <a href=\"http://www.adobe.com.br/products/acrobat/readstep2.html\" target=\"new\"><br>
                  <img src=\"imagens/acrobat.gif\" width=\"88\" height=\"31\" border=\"0\">
                </a>
              </span>
            </center>";
      }
      else {
        echo '
          <script type="text/javscript">
            alert("A escola nesse ano n�o possui nenhuma expedi��o de transfer�ncia");
            window.parent.fechaExpansivel(\'div_dinamico_\'+(window.parent.DOM_divs.length - 1));
          </script>';

        return TRUE;
      }
    }
    else {
      echo '
        <script  type="text/javscript">
          alert("A escola nesse ano n�o possui nenhuma expedi��o de transfer�ncia");
          window.parent.fechaExpansivel(\'div_dinamico_\'+(window.parent.DOM_divs.length-1));
        </script>';

      return TRUE;
    }
  }

  public function addCabecalho()
  {
    /**
     * Vari�vel global com objetos do CoreExt.
     * @see includes/bootstrap.php
     */
    global $coreExt;

    // Namespace de configura��o do template PDF
    $config = $coreExt['Config']->app->template->pdf;

    // Vari�vel que controla a altura atual das caixas
    $altura   = 30;
    $fonte    = 'arial';
    $corTexto = '#000000';

    // Cabe�alho
    $logo = $config->get($config->logo, 'imagens/brasao.gif');

    $this->pdf->quadrado_relativo(30, $altura, 782, 85);
    $this->pdf->insertImageScaled('gif', $logo, 50, 95, 41);

    // T�tulo principal
    $titulo = $config->get($config->titulo, 'i-Educar');
    $this->pdf->escreve_relativo($titulo, 30, 30, 782, 80,
      $fonte, 18, $corTexto, 'center');
    $this->pdf->escreve_relativo(date("d/m/Y"), 745, 30, 100, 80, $fonte, 12,
      $corTexto, 'left');

    // Dados escola
    $this->pdf->escreve_relativo("Institui��o: $this->nm_instituicao", 120, 52,
      300, 80, $fonte, 9, $corTexto, 'left');
    $this->pdf->escreve_relativo("Escola: {$this->nm_escola}",132, 64, 300, 80,
      $fonte, 9, $corTexto, 'left');

    $this->pdf->escreve_relativo("Registro de Transfer�ncias Expedidas - {$this->ano}",
      30, 78, 782, 80, $fonte, 12, $corTexto, 'center');

    $this->pdf->escreve_relativo("Total de Transfer�ncias: {$this->total}", 30,
      95, 782, 80, $fonte, 10, $corTexto, 'center');

    $this->pdf->linha_relativa(30, $altura += 100, 782, 0);

    $esquerda  = 30;
    $altura    = 30;
    $direita   = 782;
    $tam_texto = 10;
    $altura    = 130;

    $this->pdf->linha_relativa($esquerda, $altura, 0, 18);
    $this->pdf->escreve_relativo("Matr�cula", $esquerda + 1, $altura + 3, 150,
      30, $fonte, $tam_texto);
    $this->pdf->linha_relativa($esquerda + 55, $altura, 0, 18);

    $this->pdf->escreve_relativo("Nome Completo", $esquerda + 58, $altura + 3,
      150, 30, $fonte, $tam_texto);
    $this->pdf->linha_relativa($esquerda + 317 - 18, $altura, 0, 18);

    $this->pdf->escreve_relativo("Data", $esquerda + 320 - 18, $altura + 3,
      45, 30, $fonte, $tam_texto, $corTexto, 'center');
    $this->pdf->linha_relativa($esquerda + 365 -11, $altura, 0, 18);

    $this->pdf->escreve_relativo("S�rie", $esquerda + 355, $altura + 3, 70, 30,
      $fonte, $tam_texto, $corTexto, 'center');
    $this->pdf->linha_relativa($esquerda + 410 + 19, $altura, 0, 18);

    $this->pdf->escreve_relativo("Turma", $esquerda + 408 + 10 + 16, $altura + 3,
      35, 30, $fonte, $tam_texto, $corTexto, 'center');
    $this->pdf->linha_relativa($esquerda + 449 + 34, $altura, 0, 18);

    $this->pdf->escreve_relativo("Estabelecimento Destino", $esquerda + 452 + 37,
      $altura + 3, 150, 30, $fonte, $tam_texto);
    $this->pdf->linha_relativa($esquerda + 757, $altura, 0, 18);

    $this->pdf->escreve_relativo("UF", $esquerda + 763, $altura + 3, 50, 30,
      $fonte, $tam_texto);
    $this->pdf->linha_relativa($esquerda + 782, $altura, 0, 18);

    $this->page_y +=19;
  }


  function getNomeEscola($ref_cod_matricula_entrada) {
    $nome_escola = null;

    if (is_numeric($ref_cod_matricula_entrada)) {
      $sql = "
        SELECT
          fantasia as nome
        FROM
          pmieducar.escola,
          cadastro.juridica
        WHERE
          ref_cod_instituicao = {$this->ref_cod_instituicao}
          AND idpes = ref_idpes
          AND cod_escola = (SELECT ref_ref_cod_escola FROM pmieducar.matricula WHERE cod_matricula = {$ref_cod_matricula_entrada})
          AND ativo = 1
        UNION
        SELECT
          nm_escola
        FROM
          pmieducar.escola,
          pmieducar.escola_complemento
        WHERE
          ref_cod_instituicao = {$this->ref_cod_instituicao}
          AND cod_escola = ref_cod_escola
          AND cod_escola =
            (SELECT
               ref_ref_cod_escola
             FROM
               pmieducar.matricula
             WHERE
               cod_matricula = {$ref_cod_matricula_entrada})
          AND escola.ativo = 1;";

      $db = new clsBanco();
      $nome_escola = $db->CampoUnico($sql);
    }

    return $nome_escola;
  }

  function Editar() {
    return FALSE;
  }

  function Excluir() {
    return FALSE;
  }

}

// Instancia objeto de p�gina
$pagina = new clsIndexBase();

// Instancia objeto de conte�do
$miolo = new indice();

// Atribui o conte�do �  p�gina
$pagina->addForm($miolo);

// Gera o c�digo HTML
$pagina->MakeAll();