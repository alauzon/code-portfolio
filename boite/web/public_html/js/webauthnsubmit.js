$(function (node, child){

  $('#iregisterform').submit(function(ev){
    let self = $(this);
    ev.preventDefault();

    $('.cerror').empty().hide();

    $.ajax({url: '/?page=soumissionInscriptionEtape1',
      method: 'POST',
      data: {
        username: self.find('[name=username]').val(),
        email: self.find('[name=email]').val(),
        password: self.find('[name=password]').val(),
        webauthn: document.getElementById("webauthn").checked,
        csrf_token: self.find('[name=csrf_token]').val(),
      },
      dataType: 'json',
      success: function(j){
        /* activate the key and get the response */
        if ('message' in j) {
          sessionStorage.setItem("message", j['message']);
          $('#messages').html(j['message']);
        }
        if ('redirect' in j) {
          window.location.assign(j['redirect']);
        }
        webauthnRegister(j.challenge, function(success, info){
          if (success) {
            $.ajax({url: '/?page=soumissionInscriptionEtape2',
              method: 'POST',
              data: {register: info},
              dataType: 'json',
              success: function(j){
                if ('message' in j) {
                  sessionStorage.setItem("message", j['message']);
                }
                if ('redirect' in j) {
                  window.location.assign(j["redirect"]);
                }
                window.location.assign("/");
              },
              error: function(xhr, status, error){
                sessionStorage.setItem("message", "registration failed: "+error+": "+xhr.responseText);
                window.location.assign("/?page=login");
              }
            });
          } else {
            sessionStorage.setItem("message", "L'authentification par la clé a échoué.");
            window.location.assign("/?page=inscription");
          }
        });
      },

      error: function(xhr, status, error){
        sessionStorage.setItem("message", "impossible d'initier l'authentification : "+error+": "+xhr.responseText);
        window.location.assign("/?page=login");
      }
    });
  });

  $('#iloginform').submit(function(ev){
    var self = $(this);
    ev.preventDefault();

    $.ajax({url: '/?page=authentifier',
      method: 'POST',
      data: {
        username: self.find('[name=username]').val(),
        password: self.find('[name=password]').val(),
        csrf_token: self.find('[name=csrf_token]').val(),
      },
      dataType: 'json',
      success: function(j){
        if ('message' in j) {
          sessionStorage.setItem("message", j['message']);
        }
        if ('redirect' in j) {
          window.location.assign(j['redirect']);
        }
        /* activate the key and get the response */
        webauthnAuthenticate(j.challenge, function(success, info){
          if (success) {
            $.ajax({url: '/?page=authentifierEtape2',
              method: 'POST',
              data: {login: info},
              dataType: 'json',
              success: function(j){
                if ('message' in j) {
                  sessionStorage.setItem("message", j['message']);
                }
                if ('redirect' in j) {
                  window.location.assign(j['redirect']);
                }
              },
              error: function(xhr, status, error){
                sessionStorage.setItem("message", "login failed: "+error+": "+xhr.responseText);
                window.location.assign("/?page=login");
              }
            });
          } else {
            sessionStorage.setItem("message", info);
            window.location.assign("/?page=login");
          }
        });
      },

      error: function(xhr, status, error){
        sessionStorage.setItem("message", "couldn't initiate login: "+error+": "+xhr.responseText);
        window.location.assign("/?page=login");
      }
    });
  });

});
