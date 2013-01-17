<?php
#error_reporting(E_ALL);
#ini_set("display_errors", 1);
/**
 * i-Educar - Sistema de gestão escolar
 *
 * Copyright (C) 2006  Prefeitura Municipal de Itajaí
 *                     <ctima@itajai.sc.gov.br>
 *
 * Este programa é software livre; você pode redistribuí-lo e/ou modificá-lo
 * sob os termos da Licença Pública Geral GNU conforme publicada pela Free
 * Software Foundation; tanto a versão 2 da Licença, como (a seu critério)
 * qualquer versão posterior.
 *
 * Este programa é distribuí­do na expectativa de que seja útil, porém, SEM
 * NENHUMA GARANTIA; nem mesmo a garantia implí­cita de COMERCIABILIDADE OU
 * ADEQUAÇÃO A UMA FINALIDADE ESPECÍFICA. Consulte a Licença Pública Geral
 * do GNU para mais detalhes.
 *
 * Você deve ter recebido uma cópia da Licença Pública Geral do GNU junto
 * com este programa; se não, escreva para a Free Software Foundation, Inc., no
 * endereço 59 Temple Street, Suite 330, Boston, MA 02111-1307 USA.
 *
 * @author    Lucas D'Avila <lucasdavila@portabilis.com.br>
 * @category  i-Educar
 * @license   @@license@@
 * @package   Portabilis
 * @since     Arquivo disponível desde a versão 1.1.0
 * @version   $Id$
 */

require_once 'lib/Portabilis/View/Helper/DynamicInput/Core.php';


/**
 * Portabilis_View_Helper_DynamicInput_ComponenteCurricular class.
 *
 * @author    Lucas D'Avila <lucasdavila@portabilis.com.br>
 * @category  i-Educar
 * @license   @@license@@
 * @package   Portabilis
 * @since     Classe disponível desde a versão 1.1.0
 * @version   @@package_version@@
 */
class Portabilis_View_Helper_DynamicInput_ComponenteCurricular extends Portabilis_View_Helper_DynamicInput_Core {

  protected function getResourceId($id = null) {
    if (! $id && $this->viewInstance->ref_cod_componente_curricular)
      $id = $this->viewInstance->ref_cod_componente_curricular;

    return $id;
  }

  protected function getOptions($turmaId, $resources) {
    return $this->insertOption(null, "Selecione um componente curricular", array());
  }

  public function componenteCurricular($options = array()) {
    $defaultOptions       = array('id'        => null,
                                  'turmaId'   => null,
                                  'options'   => array(),
                                  'resources' => array());

    $options              = $this->mergeOptions($options, $defaultOptions);
    $resources            = $this->getOptions($options['turmaId'], $options['resources']);

    $defaultSelectOptions = array('id'                       => 'ref_cod_componente_curricular',
                                  'label'                    => 'Componente Curricular',
                                  'componentes_curriculares' => $resources,
                                  'value'                    => $this->getResourceId($options['id']),
                                  'callback'                 => '',
                                  'inline'                   => false,
                                  'label_hint'               => '',
                                  'input_hint'               => '',
                                  'disabled'                 => false,
                                  'required'                 => true,
                                  'multiple'                 => false);

    $selectOptions = $this->mergeOptions($options['options'], $defaultSelectOptions);
    call_user_func_array(array($this->viewInstance, 'campoLista'), $selectOptions);

    $js = '/modules/DynamicInputs/Assets/Javascripts/DynamicComponenteCurriculares.js';
    Portabilis_View_Helper_Application::loadJavascript($this->viewInstance, $js);
  }
}
