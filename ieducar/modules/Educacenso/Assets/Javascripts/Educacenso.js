$j(document).ready(function(){

	var modalLoad = '<div id="modal_load" class="modal" style="display:none;">' +
				  	'<div style="float:left;width:100px;">' +
  					'	<img src="imagens/educacenso/load_modal_educacenso.gif" width="100px" height="100px" alt="">' +
  					'</div>'+
  					'<div style="float:right;width:300px;">'+
  					'	<p style="margin-left: 20px; margin-top: 30px;font-family: verdana, arial; font-size: 18px;">Analisando as informa&ccedil;&otilde;es</p>' +
  					'	<p id="registro_load" style="margin-left: 20px; margin-top: 10px;font-family: verdana, arial; font-size: 10px;">Analisando registro 00</p>' +
  					'</div>'+
  					'</div>';

    var headerPaginaResposta = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>'+stringUtils.toUtf8('Análise exportação')+'</title>'+
            '<link rel="stylesheet" href="../modules/Educacenso/Assets/Stylesheets/educacensoPdf.css"></head><body>'+
						'<div id="content">'+
						'  <h1 class="title">'+stringUtils.toUtf8("Análise de exportação")+'</h1>'+
						'</div>'+
						'<div id="editor"></div>';

    var paginaResposta = "";

    $j("body").append(modalLoad);
    $j("#btn_enviar").click(function(){

    	var escola = $j("#ref_cod_escola").val();
    	var dataIni = $j("#data_ini").val();
    	var dataFim = $j("#data_fim").val();

    	if (!escola || !dataIni || !dataFim){
    		alert("Preencha os dados obrigat\u00f3rios antes de continuar.");
    		return;
    	}

      $j("#modal_load").modal({
        escapeClose: false,
        clickClose: false,
        showClose: false
      });

      paginaResposta = headerPaginaResposta;
      analisaRegistro00();
    });

    var finishAnalysis = function() {
      paginaResposta += '</body></html>';

      var specialElementHandlers = {
          '#editor': function (element, renderer) {
              return true;
          }
      };

      $j.modal.close();

      var newPage = window.open();
      newPage.document.write(paginaResposta);
    }

    var montaHtmlRegistro = function(response) {
      var htmlAnalise = "<h2 class='subtitle'>"+response.title+"</h2>";

      if (response.any_error_msg) {
        htmlAnalise += "<p class='errorMessage'>"+response.msgs[0].msg+"</p>";
      } else {
        //Monta uma lista em HTML com as mensagens retornadas da análise
        htmlAnalise += "<ul>";
        for (i = 0; i < response.mensagens.length; i++) {
          htmlAnalise += "<li>"+response.mensagens[i].text+"</li>";
          htmlAnalise += "<p>"+response.mensagens[i].path+"</p>";
        }
        htmlAnalise +="</ul>";
      }
      paginaResposta += htmlAnalise;
    };

    var analisaRegistro00 = function(){
        var urlForGetAnaliseRegistro = getResourceUrlBuilder.buildUrl('/module/Api/EducacensoAnalise', 'registro-00', {
          escola : $j("#ref_cod_escola").val(),
          ano    : $j("#ano").val()
        });

        var options = {
          url : urlForGetAnaliseRegistro,
          dataType : 'json',
          success  : handleGetAnaliseRegistro00
        };
        getResources(options);
    };

    var handleGetAnaliseRegistro00 = function(response) {
      montaHtmlRegistro(response);
      $j("#registro_load").val("Analisando registro 10");
      analisaRegistro10();
    };

    var analisaRegistro10 = function(){
        var urlForGetAnaliseRegistro = getResourceUrlBuilder.buildUrl('/module/Api/EducacensoAnalise', 'registro-10', {
          escola : $j("#ref_cod_escola").val(),
          ano    : $j("#ano").val()
        });

        var options = {
          url : urlForGetAnaliseRegistro,
          dataType : 'json',
          success  : handleGetAnaliseRegistro10
        };
        getResources(options);
    };

    var handleGetAnaliseRegistro10 = function(response) {
      montaHtmlRegistro(response);
      finishAnalysis();
    };


});