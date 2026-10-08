$(document).ready(function () {
    // Función para formatear números con separador de miles
    window.formatNumber_js = function (num) {
        if (!num && num !== 0) return "0";
        var parts = num.toString().split(".");
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        return parts.join(",");
    };

    $('#busquedaProd').focus();

    $('.btnMenu').click(function (e) {
        e.preventDefault();
        if ($('nav').hasClass('viewMenu')) {
            $('nav').removeClass('viewMenu');
        } else {
            $('nav').addClass('viewMenu');
        }
    });

    $('nav ul li').click(function () {
        $('nav ul li ul').slideUp();
        $(this).children('ul').slideToggle();
    });


    //--------------------- SELECCIONAR FOTO PRODUCTO ---------------------
    $("#foto").on("change", function () {
        var uploadFoto = document.getElementById("foto").value;
        var foto = document.getElementById("foto").files;
        var nav = window.URL || window.webkitURL;
        var contactAlert = document.getElementById('form_alert');

        if (uploadFoto != '') {
            var type = foto[0].type;
            var name = foto[0].name;
            if (type != 'image/jpeg' && type != 'image/jpg' && type != 'image/png') {
                contactAlert.innerHTML = '<p class="errorArchivo">El archivo no es válido.</p>';
                $("#img").remove();
                $(".delPhoto").addClass('notBlock');
                $('#foto').val('');
                return false;
            } else {
                contactAlert.innerHTML = '';
                $("#img").remove();
                $(".delPhoto").removeClass('notBlock');
                var objeto_url = nav.createObjectURL(this.files[0]);
                $(".prevPhoto").append("<img id='img' src=" + objeto_url + ">");
                $(".upimg label").remove();

            }
        } else {
            alert("No selecciono foto");
            $("#img").remove();
        }
    });

    $('.delPhoto').click(function () {
        $('#foto').val('');
        $(".delPhoto").addClass('notBlock');
        $("#img").remove();

        if ($("#foto_actual") && $("#foto_remove")) {
            $("#foto_remove").val('img_producto.png');
        }

    });

    //Modal for add product//
    $('.add_product').click(function (e) {
        /*Act on the event*/
        e.preventDefault();
        var producto = $(this).attr('product');
        var action = 'infoProducto';

        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            async: true,
            data: { action: action, producto: producto },

            success: function (response) {
                if (response != 'error') {
                    var info = JSON.parse(response);

                    //$('#producto_id').val(info.codproducto);
                    //$('.nameProducto').html(info.descripcion);

                    $('.bodyModal').html('<form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); sendDataProduct();">' +
                        '<h1><i class="fas fa-cubes" style="font-size: 45pt;"></i> <br> Agregar Producto</h1>' +
                        '<h2 class="nameProducto">' + info.descripcion + '</h2> <br>' +
                        '<input type="number" name="cantidad" id="txtCantidad" placeholder="Cantidad del producto" required min="1"><br>' +
                        '<input type="number" name="precio" id="txtPrecio" placeholder="Precio del producto" required min="1">' +
                        '<input type="hidden" name="producto_id" id="producto_id" value="' + info.codproducto + '" required>' +
                        '<input type="hidden" name="action" value="addProduct" required>' +
                        '<div class="alert alertAddProduct"></div>' +
                        '<a href="#" class="btn_ok closeModal" onclick="coloseModal(); "><i class="fas fa-ban"></i> Cerrar</a>' +
                        '<button type="submit" class="btn_new"><i class="fas fa-plus"></i> Agregar</button>' +
                        '</form>');
                }
            },

            error: function (error) {
                console.log(error);
            }

        });

        $('.modal').fadeIn();

    });

    //Modal for Delete product//
    $('.del_product').click(function (e) {
        /*Act on the event*/
        e.preventDefault();
        var producto = $(this).attr('product');
        var action = 'infoProducto';

        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            async: true,
            data: { action: action, producto: producto },

            success: function (response) {
                if (response != 'error') {
                    var info = JSON.parse(response);

                    //$('#producto_id').val(info.codproducto);
                    //$('.nameProducto').html(info.descripcion);

                    $('.bodyModal').html('<form action="" method="post" name="form_del_product" id="form_del_product" onsubmit="event.preventDefault(); delProduct();">' +
                        '<h1><i class="fas fa-cubes" style="font-size: 45pt;"></i> <br> Eliminar Producto</h1>' +
                        '<p>¿Está seguro de eliminar el siguiente registro?</p>' +
                        '<h2 class="nameProducto">' + info.descripcion + '</h2> <br>' +
                        '<input type="hidden" name="producto_id" id="producto_id" value="' + info.codproducto + '" required>' +
                        '<input type="hidden" name="action" value="delProduct" required>' +
                        '<div class="alert alertAddProduct"></div>' +
                        '<a href="#" class="btn_ok" onclick="coloseModal();"><i class="fas fa-ban"></i> Cerrar</a>' +
                        '<button type="submit" class="btn_ok"><i class="far fa-trash-alt"></i> Eliminar</button>' +
                        '</form>');
                }
            },

            error: function (error) {
                console.log(error);
            }

        });

        $('.modal').fadeIn();

    });

    $('#search_proveedor').change(function (e) {
        e.preventDefault();
        var sistema = getUrl();
        location.href = sistema + 'buscar_productos.php?proveedor=' + $(this).val();
    });

    //Activa compo par registrar cliente
    $('.btn_new_cliente').click(function (e) {
        e.preventDefault();
        $('#nit_cliente').removeAttr('disabled');
        $('#tel_cliente').removeAttr('disabled');
        $('#dir_cliente').removeAttr('disabled');

        $('#div_registro_cliente').slideDown();
    });

    //Buscar Cliente
    /*$('#nit_cliente').keyup(function(e){
        e.preventDefault();

        var cl = $(this).val();
        var action = 'searchCliente';

        $.ajax({
            url: 'ajax.php',
            type: "POST",
            async : true,
            data: {action:action,cliente:cl},

            success: function(response)
            {
                if (response == 0) {
                    $('#idcliente').val('');
                    $('#nom_cliente').val('');
                    $('#tel_cliente').val('');
                    $('#dir_cliente').val('');
                    //Mostrar boton agregar
                    $('.btn_new_cliente').slideDown();
                }else{
                    var data = $.parseJSON(response);
                    $('#idcliente').val(data.idcliente);
                    $('#nom_cliente').val(data.nombre);
                    $('#tel_cliente').val(data.telefono);
                    $('#dir_cliente').val(data.direccion);
                    //Quitar boton agregar
                    $('.btn_new_cliente').slideUp();

                    //Bloque campos
                    $('#nom_cliente').attr('disabled','disabled');
                    $('#tel_cliente').attr('disabled','disabled');
                    $('#dir_cliente').attr('disabled','disabled');

                    //Ocultar boton guardar
                    $('#div_registro_cliente').slideUp();
                }
            },
            error: function(error){
            }
        });
    });*/

    //Buscar Cliente
    $('#nom_cliente').keyup(function (e) {
        e.preventDefault();

        var cl = $(this).val();
        var action = 'searchCliente';

        $.ajax({
            url: 'ajax.php',
            type: "POST",
            async: true,
            data: { action: action, cliente: cl },

            success: function (response) {
                if (response == 0) {
                    $('#idcliente').val('');
                    $('#nit_cliente').val('');
                    $('#tel_cliente').val('');
                    $('#dir_cliente').val('');
                    //Mostrar boton agregar
                    $('.btn_new_cliente').slideDown();
                } else {
                    var data = $.parseJSON(response);
                    $('#idcliente').val(data.idcliente);
                    $('#nit_cliente').val(data.nit);
                    $('#tel_cliente').val(data.telefono);
                    $('#dir_cliente').val(data.direccion);
                    //Quitar boton agregar
                    $('.btn_new_cliente').slideUp();

                    //Bloque campos
                    $('#nit_cliente').attr('disabled', 'disabled');
                    $('#tel_cliente').attr('disabled', 'disabled');
                    $('#dir_cliente').attr('disabled', 'disabled');

                    //Ocultar boton guardar
                    $('#div_registro_cliente').slideUp();
                }
            },
            error: function (error) {
            }
        });
    });

    //Crear cliente - Ventas
    $('#form_new_cliente_venta').submit(function (e) {
        e.preventDefault();

        if ($('#nit_cliente').attr('disabled')) {
            return false;
        }

        if ($('#tel_cliente').attr('disabled')) {
            return false;
        }

        if ($('#dir_cliente').attr('disabled')) {
            return false;
        }

        $.ajax({
            url: 'ajax.php',
            type: "POST",
            async: true,
            data: $('#form_new_cliente_venta').serialize(),

            success: function (response) {
                if (response != 'error') {
                    //Agregar id al input hidden
                    $('#idcliente').val(response);
                    //Bloque campos
                    $('#nit_cliente').attr('disabled', 'disabled');
                    $('#tel_cliente').attr('disabled', 'disabled');
                    $('#dir_cliente').attr('disabled', 'disabled');

                    //Ocultar boton gagregar
                    $('.btn_new_cliente').slideUp();
                    //Ocultar boton guardar
                    $('#div_registro_cliente').slideUp();
                }
            },
            error: function (error) {
            }
        });
    });

    //Buscar Producto
    $('#txt_cod_producto').keyup(function (e) {
        e.preventDefault();

        var producto = $(this).val();
        var action = 'infoProducto';
        if (producto != '') {
            $.ajax({
                url: 'ajax.php',
                type: "POST",
                async: true,
                data: { action: action, producto: producto },

                success: function (response) {
                    //console.log(response);
                    if (response != 'error') {
                        var info = JSON.parse(response);
                        $('#txt_id_producto').val(info.codproducto);
                        $('#txt_descripcion').html(info.descripcion);
                        $('#txt_existencia').html(info.existencia);
                        $('#txt_cant_producto').val('1');
                        $('#txt_precio').val(info.precio);
                        $('#txt_precio_total').html(info.precio);

                        //Activar cantidad
                        $('#txt_cant_producto').removeAttr('disabled');
                        $('#txt_precio').removeAttr('disabled');
                        $('#txt_id_producto').removeAttr('disabled');

                        //Mostrar boton agregar
                        $('#add_product_venta').slideDown();
                    } else {
                        $('#txt_descripcion').html('-');
                        $('#txt_existencia').html('-');
                        $('#txt_cant_producto').val('0');
                        $('#txt_precio').val('0.00');
                        $('#txt_precio_total').html('0.00');

                        //Bloquear cantidad
                        $('#txt_cant_producto').attr('disabled', 'disabled');

                        //Ocultar boton agregar
                        $('#add_product_venta').slideUp();

                    }
                },
                error: function (error) {
                }
            });
        }
    });

    //Buscar Producto Compras
    $('#txt_cod_producto_compra').focus();
    $('#txt_cod_producto_compra').keyup(function (e) {
        e.preventDefault();

        var producto = $(this).val();
        var action = 'infoProducto';
        if (producto != '') {
            $.ajax({
                url: 'ajax.php',
                type: "POST",
                async: true,
                data: { action: action, producto: producto },

                success: function (response) {
                    //console.log(response);
                    if (response != 'error') {
                        var info = JSON.parse(response);
                        $('#txt_id_producto_compra').val(info.codproducto);
                        $('#txt_descripcion_compra').html(info.descripcion);
                        $('#txt_existencia_compra').html(info.existencia);
                        $('#txt_cant_producto_compra').val('1');
                        $('#txt_precio_compra').val(info.costo);
                        $('#txt_precio_total_compra').html(info.costo);

                        //Activar cantidad
                        $('#txt_cant_producto_compra').removeAttr('disabled');
                        $('#txt_precio_compra').removeAttr('disabled');
                        $('#txt_id_producto_compra').removeAttr('disabled');

                        //Mostrar boton agregar
                        $('#add_product_venta').slideDown();
                    } else {
                        $('#txt_descripcion_compra').html('-');
                        $('#txt_existencia_compra').html('-');
                        $('#txt_cant_producto_compra').val('0');
                        $('#txt_precio_compra').html('0.00');
                        $('#txt_precio_total_compra').html('0.00');

                        //Bloquear cantidad
                        $('#txt_cant_producto_compra').attr('disabled', 'disabled');

                        //Ocultar boton agregar
                        $('#add_product_venta').slideUp();

                    }
                },
                error: function (error) {
                }
            });
        }
    });

    //Validar cantidad del producto antes de agregar
    $('#txt_cant_producto').keyup(function (e) {
        e.preventDefault();
        var precio_total = $(this).val() * $('#txt_precio').val();
        var existencia = parseInt($('#txt_existencia').html());
        $('#txt_precio_total').html(precio_total);

        //Ocultar el boton agregar si la cantidad es menor a 1
        if (($(this).val() < 1 || isNaN($(this).val())) || ($(this).val() > existencia)) {
            $('#add_product_venta').slideUp();
        } else {
            $('#add_product_venta').slideDown();
        }
    });

    //Validar cantidad del producto antes de agregar
    $('#txt_cant_producto_compra').keyup(function (e) {
        e.preventDefault();
        console.log($(this).val());
        var precio_total = $(this).val() * $('#txt_precio_compra').val();
        var existencia = parseInt($('#txt_existencia_compra').html());
        $('#txt_precio_total_compra').html(precio_total);

    });

    //Cambiar Password
    $('.newPass').keyup(function () {
        validPass();
    });

    //Form cambiar contraseña
    $('#frmChangePass').submit(function (e) {
        e.preventDefault();

        var passActual = $('#txtPassUser').val();
        var passNuevo = $('#txtNewPassUser').val();
        var confirmPassNuevo = $('#txtPassConfirm').val();
        var action = "changePassword";

        if (passNuevo != confirmPassNuevo) {
            $('.alertChangePass').html('<p style="color:red;">Las contraseñas no son iguales.</p>');
            $('.alertChangePass').slideDown();
            return false;
        }

        if (passNuevo.length < 4) {
            $('.alertChangePass').html('<p style="color:red;">La contraseña debe ser de 4 caracteres como mínimo.</p>');
            $('.alertChangePass').slideDown();
            return false;
        }
        $.ajax({
            url: 'ajax.php',
            type: "POST",
            async: true,
            data: { action: action, passActual: passActual, passNuevo: passNuevo },

            success: function (response) {

                if (response != 'error') {
                    var info = JSON.parse(response);
                    if (info.cod == '00') {
                        $('.alertChangePass').html('<p style="color:green;">' + info.msg + '</p>');
                        $('#frmChangePass')[0].reset();
                    } else {
                        $('.alertChangePass').html('<p style="color:red;">' + info.msg + '</p>');
                    }
                    $('.alertChangePass').slideDown();
                }
            },
            error: function (error) {
            }
        });

    });


    //Actualizar datos de la empresa
    $('#frmEmpresa').submit(function (e) {
        e.preventDefault();

        var intNit = $('#txtNit').val();
        var strNombreEmp = $('#txtNombre').val();
        var strRsocialEmp = $('#txtRSocial').val();
        var intTelEmp = $('#txtTelEmpresa').val();
        var strEmailEmp = $('#txtEmailEmpresa').val();
        var strDirEmp = $('#txtDirEmpresa').val();
        var intMoneda = $('#txtMoneda').val();
        var intIva = $('#txtIva').val();
        var parametros = new FormData($('#frmEmpresa')[0]);

        if (intNit == '' || strNombreEmp == '' || intTelEmp == '' || strEmailEmp == '' || strDirEmp == '') {
            $('.alertFormEmpresa').html('<p style="color:red;">Todos los campos son obligatorio.</p>');
            $('.alertFormEmpresa').slideDown();
            return false;
        }

        $.ajax({
            url: 'ajax.php',
            type: "POST",
            async: true,
            data: parametros,
            contentType: false,
            processData: false,


            beforeSend: function () {
                $('.alertFormEmpresa').slideUp();
                $('.alertFormEmpresa').html('');
                $('#frmEmpresa input').attr('disabled', 'disabled');

            },

            success: function (response) {

                console.log(response);
                var info = JSON.parse(response);
                if (info.cod == '00') {
                    $('.alertFormEmpresa').html('<p style="color: #23922d;">' + info.msg + '</p>');
                    $('.alertFormEmpresa').slideDown();
                } else {
                    $('.alertFormEmpresa').html('<p style="color:red;">' + info.msg + '</p>');
                }
                $('.alertFormEmpresa').slideDown();
                $('#frmEmpresa input').removeAttr('disabled');

            },
            error: function (error) {
            }
        });
    });

    //Agregar cliente desde ventas
    $('.buscarCliente').click(function (e) {
        e.preventDefault();
        serchForDetalleCli();
        $('.modalBuscarCl').fadeIn();


    });

    $('#busquedaCli').focus();
    $('#busquedaCli').keyup(function (e) {
        e.preventDefault();

        var valorBusqueda = $(this).val();
        //console.log(valorBusqueda);

        if (valorBusqueda != "") {
            serchForDetalleCli(1, valorBusqueda);
        } else {
            serchForDetalleCli(1, '');
        }


    });

    serchForDetalleProd('', 1);
    //$('#busquedaProd').focus();
    $('#busquedaProd').keyup(function (e) {
        e.preventDefault();

        var valorBusqueda = $(this).val();
        //console.log(valorBusqueda);

        if (valorBusqueda != "") {
            serchForDetalleProd(valorBusqueda, 1);
        } else {
            serchForDetalleProd('', 1);
        }

    });

    $("body").on("click", "#paginadorProd li a", function (e) {
        e.preventDefault();
        valorhref = $(this).attr("href");
        valorBuscar = $("input[name=busquedaProd]").val();
        //console.log(valorhref);
        serchForDetalleProd(valorBuscar, valorhref);
    });

    $('#busquedaProdNombre').keyup(function (e) {
        e.preventDefault();

        var valorBusqueda = $(this).val();
        //console.log(valorBusqueda);

        if (valorBusqueda != "") {
            serchForDetalleProd(valorBusqueda, 1);
        } else {
            serchForDetalleProd('', 1);
        }

    });

    $("body").on("click", "#paginadorProd li a", function (e) {
        e.preventDefault();
        valorhref = $(this).attr("href");
        valorBuscar = $("input[name=busquedaProdNombre]").val();
        //console.log(valorhref);
        serchForDetalleProd(valorBuscar, valorhref);
    });

    serchForDetalleProdCompra('', 1);
    $('#busquedaProdCompra').focus();
    $('#busquedaProdCompra').keyup(function (e) {
        e.preventDefault();

        var valorBusqueda = $(this).val();
        //console.log(valorBusqueda);

        if (valorBusqueda != "") {
            serchForDetalleProdCompra(valorBusqueda, 1);
        } else {
            serchForDetalleProdCompra('', 1);
        }

    });

    $("body").on("click", "#paginadorProdCompra li a", function (e) {
        e.preventDefault();
        valorhref = $(this).attr("href");
        valorBuscar = $("input[name=busquedaProdCompra]").val();
        //console.log(valorhref);
        serchForDetalleProdCompra(valorBuscar, valorhref);
    });

    //Lista cliente
    listaCliente('', 1, 10);

    $('#busquedaCliente').focus();
    $('#busquedaCliente').keyup(function (e) {
        e.preventDefault();

        var valorBusqueda = $(this).val();
        var valoroption = $("#cantidad_mostrar_clientes").val();
        //console.log(valorBusqueda);

        if (valorBusqueda != "") {
            listaCliente(valorBusqueda, 1, valoroption);
        } else {
            listaCliente('', 1, 10);
        }
    });

    $("body").on("click", "#paginadorClient li a", function (e) {
        e.preventDefault();
        valorhref = $(this).attr("href");
        valorBuscar = $("input[name=busquedaCliente]").val();
        valoroption = $("#cantidad_mostrar_clientes").val();
        //console.log(valorhref);
        listaCliente(valorBuscar, valorhref, valoroption);
    });

    $("#cantidad_mostrar_clientes").change(function () {
        valoroption = $(this).val();
        //console.log(valoroption);
        valorBuscar = $("input[name=busquedaCliente]").val();
        //console.log(valorBuscar);
        listaCliente(valorBuscar, 1, valoroption);
    });

    //Lista gastos
    listaCajas('', 1, 10);

    $("#busquedaCaja").change(function () {
        valorBuscar = $(this).val();
        //console.log(valoroption);
        valoroption = $("#cantidad_mostrar_caja").val();
        //console.log(valorBuscar);
        listaCajas(valorBuscar, 1, valoroption);
    });
    $("body").on("click", "#paginadoCaja li a", function (e) {
        e.preventDefault();
        valorhref = $(this).attr("href");
        valorBuscar = $("input[name=busquedaCaja]").val();
        valoroption = $("#cantidad_mostrar_caja").val();
        //console.log(valorhref);
        listaCajas(valorBuscar, valorhref, valoroption);
    });

    $("#cantidad_mostrar_caja").change(function () {
        valoroption = $(this).val();
        //console.log(valoroption);
        valorBuscar = $("input[name=busquedaCaja]").val();
        //console.log(valorBuscar);
        listaCajas(valorBuscar, 1, valoroption);
    });


    //Lista gastos
    listaGastosFiltrados();

    $('#busquedaEgresos').focus();
    $('#busquedaEgresos').keyup(function (e) {
        e.preventDefault();

        var valorBusqueda = $(this).val();
        var valoroption = $("#cantidad_mostrar_egresos").val();
        //console.log(valorBusqueda);

        if (valorBusqueda != "") {
            listaGastosFiltrados();
        } else {
            listaGastosFiltrados();
        }
    });

    $("body").on("click", "#paginadoEgresos li a", function (e) {
        e.preventDefault();
        valorhref = $(this).attr("href");
        valorBuscar = $("input[name=busquedaEgresos]").val();
        valoroption = $("#cantidad_mostrar_egresos").val();
        //console.log(valorhref);
        var f_de = $('#fecha_de').val();
        var f_a = $('#fecha_a').val();
        listaGastos(valorBuscar, valorhref, valoroption, f_de, f_a);
    });

    $("#cantidad_mostrar_egresos").change(function () {
        valoroption = $(this).val();
        //console.log(valoroption);
        valorBuscar = $("input[name=busquedaEgresos]").val();
        //console.log(valorBuscar);
        listaGastosFiltrados();
    });

    $('#nuevoCliente').click(function (e) {
        e.preventDefault();

        $('.bodyModal').html('<form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); nuevoCliente();">' +
            '<h1><i class="fas fa-user-plus" style="font-size: 45pt;"></i> <br> Registrar cliente</h1>' +
            '<input type="hidden" name="action" value="nuevoCliente" required><br>' +
            '<input type="text" name="nitCliente" id="nitCliente" value="" placeholder="Ruc o Cedula" required><br>' +
            '<input type="text" name="nombreCliente" id="nombreCliente" value="" placeholder="Nombre y apellidos" onkeypress="return soloLetras(event)" onpaste="return false" required><br>' +
            '<input type="number" name="telefonoCliente" id="telefonoCliente" value="" placeholder="Teléfono" min="0"><br>' +
            '<input type="text" name="direccionCliente" id="direccionCliente" value="" placeholder="Dirección"><br>' +
            '<div class="alert alertAddProduct"></div>' +
            '<a href="#" class="btn_ok closeModal" onclick="coloseModal(); "><i class="fas fa-ban"></i> Cerrar</a>' +
            '<button type="submit" class="btn_new"><i class="fas fa-plus"></i> Guardar</button>' +
            '</form>');
        $('.modal').fadeIn();

    });

    /* $('#nuevoEgreso').click(function (e) {
        e.preventDefault();

        $('.bodyModal').html('<form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); nuevoEgreso();">' +
            '<h1><i class="fa fa-file-alt fa-w-12" style="font-size: 45pt;"></i> <br> Registrar Egreso</h1>' +
            '<input type="hidden" name="action" value="nuevoEgreso" required><br>' +
            '<input type="text" name="descEgreso" id="descEgreso" value="" placeholder="Descripción" required><br>' +
            '<input type="number" name="cantEgreso" id="cantEgreso" value="" placeholder="Cantidad" step="any" required><br>' +
            '<div class="alert alertAddProduct"></div>' +
            '<a href="#" class="btn_ok closeModal" onclick="coloseModal(); "><i class="fas fa-ban"></i> Cerrar</a>' +
            '<button type="submit" class="btn_new"><i class="fas fa-plus"></i> Guardar</button>' +
            '</form>');
        $('.modal').fadeIn();

    }); */

    //abrir caja
    $('#abrir_caja').click(function (e) {
        e.preventDefault();

        $('.bodyModal').html('<form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); event.stopImmediatePropagation(); nuevaCaja();">' +
            '<h1><i class="fa fa-money-bill-alt fa-w-20" style="font-size: 45pt;"></i> <br> Abrir caja</h1>' +
            '<input type="hidden" name="action" value="nuevaCaja" required><br>' +
            '<label>Cantidad:</label>' +
            '<input class="textcenter" type="number" name="inicioCaja" id="inicioCaja" value="" placeholder="C$ 0.00" required min="0"><br>' +
            '<div class="alert alertAddProduct"></div>' +
            '<a href="#" class="btn_ok closeModal" onclick="coloseModal(); "><i class="fas fa-ban"></i> Cerrar</a>' +
            '<button type="submit" class="btn_new"><i class="fas fa-plus"></i> Guardar</button>' +
            '</form>');
        $('.modal').fadeIn();

    });

    //Devolucion
    $('#devolucion').click(function (e) {
        e.preventDefault();

        $('.bodyModal').html('<form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); devolucion();">' +
            '<h1><i class="fas fa-cube" style="font-size: 45pt;"></i> <br> Devolución</h1>' +
            '<input type="hidden" name="action" value="devolucion" required>' +
            '<label>No. Venta</label>' +
            '<input style="text-align:center;" type="text" name="noVenta_dev" id="noVenta_dev" value="" placeholder="No. venta" required>' +
            '<label>Producto</label>' +
            '<input style="text-align:center;" type="text" name="codProducto_dev" id="codProducto_dev" value="" placeholder="Código del producto" required>' +
            '<label>Cantidad</label>' +
            '<input style="text-align:center;" type="number" name="cantProducto_dev" id="cantProducto_dev" value="1" placeholder="" required min="1"><br>' +
            '<div class="alert alertAddProduct"></div>' +
            '<a href="#" class="btn_ok closeModal" onclick="coloseModal(); "><i class="fas fa-ban"></i> Cerrar</a>' +
            '<button type="submit" class="btn_new"><i class="fas fa-plus"></i> Guardar</button>' +
            '</form>');
        $('.modal').fadeIn();

    });

    //Lista Usuarios
    listaUsuario('', 1, 10);

    $('#busquedaUsuario').focus();
    $('#busquedaUsuario').keyup(function (e) {
        e.preventDefault();

        var valorBusqueda = $(this).val();
        var valoroption = $("#cantidad_mostrar_usuarios").val();
        //console.log(valorBusqueda);

        if (valorBusqueda != "") {
            listaUsuario(valorBusqueda, 1, valoroption);
        } else {
            listaUsuario('', 1, valoroption);
        }
    });

    $("body").on("click", "#paginadorUsuario li a", function (e) {
        e.preventDefault();
        valorhref = $(this).attr("href");
        valorBuscar = $("input[name=busquedaUsuario]").val();
        valoroption = $("#cantidad_mostrar_usuarios").val();
        //console.log(valorhref);
        listaUsuario(valorBuscar, valorhref, valoroption);
    });

    $("#cantidad_mostrar_usuarios").change(function () {
        valoroption = $(this).val();
        //console.log(valoroption);
        valorBuscar = $("input[name=busquedaUsuario]").val();
        //console.log(valorBuscar);
        listaUsuario(valorBuscar, 1, valoroption);
    });

    $('#nuevoUsuario').click(function (e) {
        e.preventDefault();
        var action = 'selecionarRol';
        $.ajax({
            url: 'ajax.php',
            type: "POST",
            data: { action: action },

            success: function (response) {
                //console.log(response);
                var info = JSON.parse(response);

                $('.bodyModal').html('<form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); nuevoUsuario();">' +
                    '<h1><i class="fas fa-user-plus" style="font-size: 45pt;"></i> <br> Registrar usuario</h1>' +
                    '<input type="hidden" name="action" value="nuevoUsuario" required><br>' +
                    '<input type="text" name="nombreUsuario" id="nombreUsuario" value="" placeholder="Nombre completo" onkeypress="return soloLetras(event)" onpaste="return false" required><br>' +
                    '<input type="email" name="correoUsuario" id="correoUsuario" value="" placeholder="Correo" required><br>' +
                    '<input type="text" name="usuario" id="usuario" value="" placeholder="Usuarios" required><br>' +
                    '<input type="password" name="claveUsuario" id="claveUsuario" value="" placeholder="Clave" required><br>' +
                    '<select name="rolUsuario" id="rolUsuario" required>' + info.rol + '</select><br>' +
                    '<div class="alert alertAddProduct"></div>' +
                    '<a href="#" class="btn_ok closeModal" onclick="coloseModal(); "><i class="fas fa-ban"></i> Cerrar</a>' +
                    '<button type="submit" class="btn_new"><i class="fas fa-plus"></i> Guardar</button>' +
                    '</form>');
            },
            error: function (error) {
                console.log(error);
            }
        });
        $('.modal').fadeIn();

    });

    //Modal estado de resultado
    $('#estado_resultado').click(function (e) {
        e.preventDefault();
        var action = 'selecionarRol';
        $.ajax({
            url: 'ajax.php',
            type: "POST",
            data: { action: action },

            success: function (response) {
                //console.log(response);
                var info = JSON.parse(response);

                $('.bodyModal').html('<form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); generarEstado();">' +
                    '<h1><i class="far fa-file-alt" style="font-size: 45pt;"></i> <br> Generar Estado de Resultado</h1>' +
                    '<input type="hidden" name="action" value="estadoResultado" required>' +
                    '<label style="text-align:left !important;">Desde el:</label>' +
                    '<input type="date" name="desde" id="desde" value="" required><br>' +
                    '<label style="text-align:left !important;">Hasta el:</label>' +
                    '<input type="date" name="hasta" id="hasta" value="" required><br>' +
                    '<div class="alert alertAddProduct"></div>' +
                    '<a href="#" class="btn_ok closeModal" onclick="coloseModal(); "><i class="fas fa-ban"></i> Cerrar</a>' +
                    '<button type="submit" class="btn_new"><i class="far fa-file-alt"></i> Generar</button>' +
                    '</form>');
            },
            error: function (error) {
                console.log(error);
            }
        });
        $('.modal').fadeIn();

    });

    //Lista proveedor
    listaProveedor('', 1, 10);

    $('#busquedaProveedor').focus();
    $('#busquedaProveedor').keyup(function (e) {
        e.preventDefault();

        var valorBusqueda = $(this).val();
        var valoroption = $("#cantidad_mostrar_proveedor").val();
        //console.log(valorBusqueda);

        if (valorBusqueda != "") {
            listaProveedor(valorBusqueda, 1, valoroption);
        } else {
            listaProveedor('', 1, valoroption);
        }
    });

    $("body").on("click", "#paginadorProveedor li a", function (e) {
        e.preventDefault();
        valorhref = $(this).attr("href");
        valorBuscar = $("input[name=busquedaProveedor]").val();
        valoroption = $("#cantidad_mostrar_proveedor").val();
        //console.log(valorhref);
        listaProveedor(valorBuscar, valorhref, valoroption);
    });

    $("#cantidad_mostrar_proveedor").change(function () {
        valoroption = $(this).val();
        //console.log(valoroption);
        valorBuscar = $("input[name=busquedaProveedor]").val();
        //console.log(valorBuscar);
        listaProveedor(valorBuscar, 1, valoroption);
    });

    $('#nuevoProveedor').click(function (e) {
        e.preventDefault();

        $('.bodyModal').html('<form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); nuevoProveedor();">' +
            '<h1><i class="far fa-building " style="font-size: 45pt;"></i> <br> Registrar proveedor</h1>' +
            '<input type="hidden" name="action" value="nuevoProveedor" required><br>' +
            '<input type="text" name="nombreProveedor" id="nombreProveedor" value="" placeholder="Nombre del proveedor" onkeypress="return soloLetras(event)" onpaste="return false" required><br>' +
            '<input type="text" name="nombreContacto" id="nombreContacto" value="" placeholder="Nombre del contacto" onkeypress="return soloLetras(event)" onpaste="return false" required><br>' +
            '<input type="number" name="telefonoProveedor" id="telefonoProveedor" value="" placeholder="Teléfono" required><br>' +
            '<input type="text" name="direccionProveedor" id="direccionProveedor" value="" placeholder="Dirección" required><br>' +
            '<div class="alert alertAddProduct"></div>' +
            '<a href="#" class="btn_ok closeModal" onclick="coloseModal(); "><i class="fas fa-ban"></i> Cerrar</a>' +
            '<button type="submit" class="btn_new"><i class="fas fa-plus"></i> Guardar</button>' +
            '</form>');
        $('.modal').fadeIn();

    })

    //Lista productos
    listaProductos('', 1, 10);

    $('#busquedaProducto').focus();
    $('#busquedaProducto').keyup(function (e) {
        e.preventDefault();

        var valorBusqueda = $(this).val();
        var valoroption = $("#cantidad_mostrar_producto").val();
        //console.log(valorBusqueda);

        if (valorBusqueda != "") {
            listaProductos(valorBusqueda, 1, valoroption);
        } else {
            listaProductos('', 1, valoroption);
        }

    });

    $("body").on("click", "#paginadorProducto li a", function (e) {
        e.preventDefault();
        valorhref = $(this).attr("href");
        valorBuscar = $("input[name=busquedaProducto]").val();
        valoroption = $("#cantidad_mostrar_producto").val();
        //console.log(valorhref);
        listaProductos(valorBuscar, valorhref, valoroption);
    });

    $("#cantidad_mostrar_producto").change(function () {
        valoroption = $(this).val();
        //console.log(valoroption);
        valorBuscar = $("input[name=busquedaProducto]").val();
        //console.log(valorBuscar);
        listaProductos(valorBuscar, 1, valoroption);
    });

    $('#nuevoProducto').click(function (e) {
        e.preventDefault();
        var action = 'selecionarProveedor';
        $.ajax({
            url: 'ajax.php',
            type: "POST",
            data: { action: action },

            success: function (response) {
                console.log(response);
                var info = JSON.parse(response);

                $('.bodyModal').html('<form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); nuevoProducto();">' +
                    '<h1><i class="fa fa-cube " style="font-size: 25pt;"></i> <br> Registrar producto</h1>' +
                    '<input type="hidden" name="action" value="nuevoProducto" required>' +
                    '<select name="nombreProv" id="nombreProv" class="notItemOne" style="margin-bottom:5px;">' + info.proveedor + '</select>' +
                    '<div style="display:flex; gap:5px; margin-bottom:5px;">' +
                    '<select name="categoriaProd" id="categoriaProd" style="margin-bottom:0; flex:1;">' + info.categoria + '</select>' +
                    '<button type="button" class="btn_new" style="background:#2c3e50; border-color:#2c3e50; padding:0 12px; margin:0;" onclick="nuevaCategoriaRapida(\'categoriaProd\')" title="Nueva Categoría"><i class="fas fa-plus"></i></button>' +
                    '</div>' +
                    '<div style="display:flex; gap:5px; margin-bottom:5px;">' +
                    '<select name="marcaProd" id="marcaProd" style="margin-bottom:0; flex:1;">' + info.marca + '</select>' +
                    '<button type="button" class="btn_new" style="background:#2c3e50; border-color:#2c3e50; padding:0 12px; margin:0;" onclick="nuevaMarcaRapida(\'marcaProd\')" title="Nueva Marca"><i class="fas fa-plus"></i></button>' +
                    '</div>' +
                    '<div style="display:flex; gap:5px; margin-bottom:5px;">' +
                    '<select name="tamanoProd" id="tamanoProd" style="margin-bottom:0; flex:1;">' + info.tamano + '</select>' +
                    '<button type="button" class="btn_new" style="background:#2c3e50; border-color:#2c3e50; padding:0 12px; margin:0;" onclick="nuevoTamanoRapido(\'tamanoProd\')" title="Nuevo Tamaño"><i class="fas fa-plus"></i></button>' +
                    '</div>' +
                    '<div style="display:flex; gap:5px; align-items:center; margin-bottom:5px;">' +
                    '<input type="text" name="codigoProd" id="codigoProd" value="" placeholder="Código de barras" required style="margin-bottom:0; flex:1;">' +
                    '<button type="button" class="btn_new" style="background:#2c3e50; border-color:#2c3e50; padding:8px 12px; font-size:12px; line-height:1; height:auto; display:inline-flex; align-items:center; gap:4px; margin:0;" onclick="generarCodigoForm(\'codigoProd\', \'barcodePreviewAdd\')"><i class="fas fa-barcode"></i> Generar</button>' +
                    '</div>' +
                    '<input type="text" name="codigoInternoProd" id="codigoInternoProd" value="" placeholder="Código interno (opcional, ej: REF-001)" style="margin-bottom:5px;">' +
                    '<div style="text-align:center; margin-bottom:5px;">' +
                    '<canvas id="barcodePreviewAdd" style="max-width:100%; height:auto; display:none;"></canvas>' +
                    '</div>' +
                    '<input type="text" name="nombreProd" id="nombreProd" value="" placeholder="Nombre del producto" required style="margin-bottom:5px;">' +
                    '<div style="display:flex; gap:5px; margin-bottom:5px;">' +
                    '<input type="number" name="costoProd" id="costoProd" value="" placeholder="Costo" step="any" required min="0" style="margin-bottom:0; flex:1;">' +
                    '<input type="number" name="precioProd" id="precioProd" value="" placeholder="Precio" step="any" required min="0" style="margin-bottom:0; flex:1;">' +
                    '</div>' +
                    '<div style="display:flex; gap:5px; margin-bottom:5px;">' +
                    '<input type="number" name="precioMayoristaProd" id="precioMayoristaProd" value="" placeholder="P. Mayorista" step="any" min="0" style="margin-bottom:0; flex:1;">' +
                    '<input type="number" name="existenciaProd" id="existenciaProd" value="" placeholder="Stock" min="0" style="margin-bottom:0; flex:1;">' +
                    '</div>' +
                    '<input type="file" name="fotoProd" id="fotoProd" value="" placeholder="Foto del producto" style="margin-bottom:5px;"><br>' +
                    '<div class="alert alertAddProduct"></div>' +
                    '<a href="#" class="btn_ok closeModal" onclick="coloseModal(); "><i class="fas fa-ban"></i> Cerrar</a>' +
                    '<button type="submit" class="btn_new"><i class="fas fa-plus"></i> Guardar</button>' +
                    '</form>');

                $('#codigoProd').on('input keyup change', function(){
                    updateBarcodePreview($(this).val(), 'barcodePreviewAdd');
                });

                // Marcas habilitadas; tamaños deshabilitados hasta seleccionar categoría
                $('#marcaProd').prop('disabled', false);
                $('#tamanoProd').html('<option value="">Seleccionar Tamaño</option>').prop('disabled', true);

                $('#categoriaProd').change(function(){
                    var catId = $(this).val();
                    if (catId != "") {
                        $.ajax({
                            url: 'ajax_categorias_marcas_tamanos.php',
                            type: 'POST',
                            data: { action: 'getMarcasYTamanosPorCategoria', categoria_id: catId },
                            success: function(resp) {
                                var info = JSON.parse(resp);
                                if (info.marcas) {
                                    $('#marcaProd').html(info.marcas);
                                }
                                if (info.tamanos) {
                                    $('#tamanoProd').html(info.tamanos).prop('disabled', false);
                                } else {
                                    $('#tamanoProd').html('<option value="">Sin tamaños para esta categoría</option>').prop('disabled', true);
                                }
                            }
                        });
                    }
                });

            },

            error: function (error) {
                console.log(error);
            }

        });

        $('.modal').fadeIn();

    });

    //Reporte por producto
    $('#reporteProducto').click(function (e) {
        e.preventDefault();
        var action = 'selecionarProveedor';
        $.ajax({
            url: 'ajax.php',
            type: "POST",
            data: { action: action },

            success: function (response) {
                //console.log(response);
                var info = JSON.parse(response);

                $('.bodyModal').html('<form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); reporteProducto();">' +
                    '<h1><i class="fa fa-cube " style="font-size: 45pt;"></i> <br> Reporte por producto</h1><br>' +
                    '<input type="text" name="codigoRepProd" id="codigoRepProd" placeholder="Codigo del producto (Opcional)">' +
                    '<label class="textleft">Desde:</label>' +
                    '<input type="date" name="inicioReporteProd" id="inicioReporteProd">' +
                    '<label class="textleft">Hasta:</label>' +
                    '<input type="date" name="finReporteProd" id="finReporteProd">' +
                    '<div class="alert alertAddProduct"></div>' +
                    '<a href="#" class="btn_ok closeModal" onclick="coloseModal(); "><i class="fas fa-ban"></i> Cerrar</a>' +
                    '<button type="submit" class="btn_new"><i class="fa fa-file-alt fa-w-12"></i> Generar</button>' +
                    '</form>');

            },

            error: function (error) {
                console.log(error);
            }

        });

        $('.modal').fadeIn();

    });

    //Lista ventas
    listaVentas('', 1, 10);

    $('#busquedaVentas').focus();
    $('#busquedaVentas').keyup(function (e) {
        e.preventDefault();
        if (e.keyCode == 13) return false; // Evitar submit al presionar Enter

        var valorBusqueda = $(this).val();
        var valoroption = $("#cantidad_mostrar_ventas").val();
        var filtro_producto = $('#filtro_producto').val();
        var filtro_pago = $('#filtro_pago').val();
        var fecha_de = $('#fecha_de').val();
        var fecha_a = $('#fecha_a').val();

        listaVentas(valorBusqueda, 1, valoroption, fecha_de, fecha_a, filtro_producto, filtro_pago);
    });

    // Evitar recarga en formularios de búsqueda de ventas
    $('.form_search, .form_search_date').submit(function (e) {
        e.preventDefault();
    });

    $("body").on("click", "#paginadorVentas li a", function (e) {
        e.preventDefault();
        valorhref = $(this).attr("href");
        valorBuscar = $("input[name=busquedaVentas]").val();
        valoroption = $("#cantidad_mostrar_ventas").val();
        var filtro_producto = $('#filtro_producto').val();
        var filtro_pago = $('#filtro_pago').val();
        var fecha_de = $('#fecha_de').val();
        var fecha_a = $('#fecha_a').val();

        listaVentas(valorBuscar, valorhref, valoroption, fecha_de, fecha_a, filtro_producto, filtro_pago);
    });

    $("#cantidad_mostrar_ventas").change(function () {
        valoroption = $(this).val();
        valorBuscar = $("input[name=busquedaVentas]").val();
        var filtro_producto = $('#filtro_producto').val();
        var filtro_pago = $('#filtro_pago').val();
        var fecha_de = $('#fecha_de').val();
        var fecha_a = $('#fecha_a').val();

        listaVentas(valorBuscar, 1, valoroption, fecha_de, fecha_a, filtro_producto, filtro_pago);
    });

    listaCompras('', 1, 10);

    $('#busquedaCompra').focus();
    $('#busquedaCompra').keyup(function (e) {
        e.preventDefault();

        var valorBusqueda = $(this).val();
        var valoroption = $("#cantidad_mostrar_compras").val();
        //console.log(valorBusqueda);

        if (valorBusqueda != "") {
            listaCompras(valorBusqueda, 1, valoroption);
        } else {
            listaCompras('', 1, valoroption);
        }
    });

    $("body").on("click", "#paginadorCompras li a", function (e) {
        e.preventDefault();
        valorhref = $(this).attr("href");
        valorBuscar = $("input[name=busquedaCompra]").val();
        valoroption = $("#cantidad_mostrar_compras").val();
        //console.log(valorhref);
        listaCompras(valorBuscar, valorhref, valoroption);
    });

    $("#cantidad_mostrar_compras").change(function () {
        valoroption = $(this).val();
        //console.log(valoroption);
        valorBuscar = $("input[name=busquedaCompra]").val();
        //console.log(valorBuscar);
        listaCompras(valorBuscar, 1, valoroption);
    });

    //Lista ventas de credito
    listaCreditos('', 1);

    $('#busquedaCredito').focus();
    $('#busquedaCredito').keyup(function (e) {
        e.preventDefault();

        var valorBusqueda = $(this).val();
        var valoroption = $("#cantidad_mostrar_porcobrar").val();
        //console.log(valorBusqueda);

        if (valorBusqueda != "") {
            listaCreditos(valorBusqueda, 1, valoroption);
        } else {
            listaCreditos('', 1, valoroption);
        }
    });

    $("body").on("click", "#paginador_por_cobrar li a", function (e) {
        e.preventDefault();
        valorhref = $(this).attr("href");
        valorBuscar = $("input[name=busquedaCredito]").val();
        valoroption = $("#cantidad_mostrar_porcobrar").val();
        //console.log(valorhref);
        listaCreditos(valorBuscar, valorhref, valoroption);
    });

    $("#cantidad_mostrar_porcobrar").change(function () {
        valoroption = $(this).val();
        //console.log(valoroption);
        valorBuscar = $("input[name=busquedaCredito]").val();
        //console.log(valorBuscar);
        listaCreditos(valorBuscar, 1, valoroption);
    });

    //Busqueda por rango de fecha ventas
    $('.btn_rango_fecha').click(function (e) {
        e.preventDefault();
        var desde = $('#fecha_de').val();
        var hasta = $('#fecha_a').val();
        var busqueda = $('#busquedaVentas').val();
        var valoroption = $("#cantidad_mostrar_ventas").val();
        var filtro_producto = $('#filtro_producto').val();
        var filtro_pago = $('#filtro_pago').val();

        // Solo retornar false si NO hay ningún filtro seleccionado
        if (desde == '' && hasta == '' && busqueda == '' && filtro_producto == '' && filtro_pago == '') {
            return false;
        }

        listaVentas(busqueda, 1, valoroption, desde, hasta, filtro_producto, filtro_pago);
    });

    //Busqueda por rango de fecha cuentas por cobrar
    $('.btn_rango_fecha_mov').click(function (e) {
        e.preventDefault();
        var desde = $('#fecha_de_mov').val();
        var hasta = $('#fecha_a_mov').val();
        var busqueda = $('#busquedaMov').val();
        var valoroption = $("#cantidad_mostrar_movcobrar").val();
        if (desde == '' || hasta == '') {
            return false;
        }

        $.ajax({
            url: 'action/data_movimientos.php',
            type: "POST",
            async: true,
            data: { fecha_de: desde, fecha_a: hasta, busqueda: busqueda, cantidad: valoroption },

            success: function (response) {
                //console.log(response);
                if (response != 'error') {
                    var info = JSON.parse(response);
                    $('#listaMovimientos').html(info.detalle);
                    $('#paginadorMovimientos').html(info.totales);

                } else {
                    $('#listaMovimientos').html('<table>' +
                        '<tr>' +
                        '<th>No.</th>' +
                        '<th>Fecha</th>' +
                        '<th>Cliente</th>' +
                        '<th>Vendedor</th>' +
                        '<th>Estado</th>' +
                        '<th class="textright">Factura</th>' +
                        '<th class="textright">Abono</th>' +
                        '<th class="textright">Saldo total</th>' +
                        '</tr>' +
                        '<tbody>' +
                        '<tr><td colspan="8">No se encontraron concidencias :(</td></tr>' +
                        '</tbody>');
                    $('#paginadorMovimientos').html('');
                    //console.log('no data');

                }
            },
            error: function (error) {
                console.log(error);
            }
        });
    });

    //Busqueda por rango de fecha cuentas por pagar
    $('.btn_rango_fecha_mov_pagar').click(function (e) {
        e.preventDefault();
        var desde = $('#fecha_de_mov_pagar').val();
        var hasta = $('#fecha_a_mov_pagar').val();
        var busqueda = $('#busquedaMov_proveedor').val();
        var valoroption = $("#cantidad_mostrar_movpagar").val();
        if (desde == '' || hasta == '') {
            return false;
        }

        $.ajax({
            url: 'action/data_mov_proveedor.php',
            type: "POST",
            async: true,
            data: { fecha_de: desde, fecha_a: hasta, busqueda: busqueda, cantidad: valoroption },

            success: function (response) {
                //console.log(response);
                if (response != 'error') {
                    var info = JSON.parse(response);
                    $('#listaMov_proveedor').html(info.detalle);
                    $('#paginadorMov_proveedor').html(info.totales);

                } else {
                    $('#listaMov_proveedor').html('<table>' +
                        '<tr>' +
                        '<th>No.</th>' +
                        '<th>Fecha</th>' +
                        '<th>Cliente</th>' +
                        '<th>Vendedor</th>' +
                        '<th>Estado</th>' +
                        '<th class="textright">Factura</th>' +
                        '<th class="textright">Abono</th>' +
                        '<th class="textright">Saldo total</th>' +
                        '</tr>' +
                        '<tbody>' +
                        '<tr><td colspan="8">No se encontraron concidencias :(</td></tr>' +
                        '</tbody>');
                    $('#paginadorMov_proveedor').html('');
                    //console.log('no data');

                }
            },
            error: function (error) {
                console.log(error);
            }
        });
    });

    //Busqueda por rango de fecha compras
    $('.btn_rango_fecha_compra').click(function (e) {
        e.preventDefault();
        var desde = $('#fecha_de_compra').val();
        var hasta = $('#fecha_a_compra').val();
        var busqueda = $('#busquedaCompra').val();
        var valoroption = $("#cantidad_mostrar_compras").val();
        if (desde == '' || hasta == '') {
            return false;
        }

        $.ajax({
            url: 'action/data_compras.php',
            type: "POST",
            async: true,
            data: { fecha_de: desde, fecha_a: hasta, busqueda: busqueda, cantidad: valoroption },

            success: function (response) {
                //console.log(response);
                if (response != 'error') {
                    var info = JSON.parse(response);
                    $('#listaCompras').html(info.detalle);
                    $('#paginadorCompras').html(info.totales);

                } else {
                    $('#listaCompras').html('<table>' +
                        '<tr>' +
                        '<th>No.</th>' +
                        '<th>Fecha</th>' +
                        '<th>Proveedor</th>' +
                        '<th>Usuario</th>' +
                        '<th>Estado</th>' +
                        '<th class="textright">Total Factura</th>' +
                        '<th class="textright">Acciones</th>' +
                        '</tr>' +
                        '<tbody>' +
                        '<tr><td colspan="7">No se encontraron concidencias :(</td></tr>' +
                        '</tbody>');
                    $('#paginadorCompras').html('');
                    //console.log('no data');

                }
            },
            error: function (error) {
                console.log(error);
            }
        });
    });

    $('#reporte_pdf').click(function (e) {
        e.preventDefault();
        var rows = $('#listaVentas tr').length;
        if (rows > 0) {
            var pagina = '';
            var busqueda = $('#busquedaVentas').val();
            var fecha_de = $('#fecha_de').val();
            var fecha_a = $('#fecha_a').val();
            var producto = $('#filtro_producto').val();
            var tipo_pago = $('#filtro_pago').val();

            if (fecha_de != '' || fecha_a != '') {
                generarReportePDF_rango_tipo(fecha_de, fecha_a, busqueda, tipo_pago, producto);
            } else {
                generarReportePDF_tipo(pagina, busqueda, tipo_pago, producto);
            }
        }
    });

    $('#descargar_excel_ventas').click(function (e) {
        e.preventDefault();
        var rows = $('#listaVentas tr').length;
        if (rows > 0) {
            var pagina = '';
            var busqueda = $('#busquedaVentas').val();
            var fecha_de = $('#fecha_de').val();
            var fecha_a = $('#fecha_a').val();
            var producto = $('#filtro_producto').val();
            var tipo_pago = $('#filtro_pago').val();

            if (fecha_de != '' || fecha_a != '') {
                descargarReporteExcel_rango(fecha_de, fecha_a, busqueda, producto, tipo_pago);
            } else {
                descargarReporteExcel(pagina, busqueda, producto, tipo_pago);
            }
        }
    });

    $('#reporte_pdf_mov').click(function (e) {
        e.preventDefault();
        var rows = $('#listaMovimientos tr').length;
        if (rows > 0) {
            var pagina = '';
            var busqueda = $('#busquedaMov').val();
            var fecha_de = $('#fecha_de_mov').val();
            var fecha_a = $('#fecha_a_mov').val();

            if (fecha_de != '' || fecha_a != '') {
                generarReportePDF_rango_mov(fecha_de, fecha_a, busqueda);
            } else {
                generarReportePDF_mov(pagina, busqueda);
            }
        }
    });

    $('#reporte_pdf_mov_pagar').click(function (e) {
        e.preventDefault();
        var rows = $('#listaMov_proveedor tr').length;
        if (rows > 0) {
            var pagina = '';
            var busqueda = $('#busquedaMov_proveedor').val();
            var fecha_de = $('#fecha_de_mov_pagar').val();
            var fecha_a = $('#fecha_a_mov_pagar').val();

            if (fecha_de != '' || fecha_a != '') {
                generarReportePDF_rango_mov_pagar(fecha_de, fecha_a, busqueda);
            } else {
                generarReportePDF_mov_pagar(pagina, busqueda);
            }
        }
    });

    $('#descargar_excel_compra').click(function (e) {
        e.preventDefault();
        var rows = $('#listaCompras tr').length;
        if (rows > 0) {
            var pagina = '';
            var busqueda = $('#busquedaCompra').val();
            var fecha_de = $('#fecha_de_compra').val();
            var fecha_a = $('#fecha_a_compra').val();

            if (fecha_de != '' || fecha_a != '') {
                descargarReporteExcel_rango_compra(fecha_de, fecha_a, busqueda);
            } else {
                descargarReporteExcel_compra(pagina, busqueda);
            }
        }
    });

    $('#reporte_pdf_compra').click(function (e) {
        e.preventDefault();
        var rows = $('#listaCompras tr').length;
        if (rows > 0) {
            var pagina = '';
            var busqueda = $('#busquedaCompra').val();
            var fecha_de = $('#fecha_de_compra').val();
            var fecha_a = $('#fecha_a_compra').val();

            if (fecha_de != '' || fecha_a != '') {
                generarReportePDF_rango_compra(fecha_de, fecha_a, busqueda);
            } else {
                generarReportePDF_compra(pagina, busqueda);
            }
        }
    });

    lista_cuentas_por_pagar('', 1);
    $('#busquedaPago').focus();
    $('#busquedaPago').keyup(function (e) {
        e.preventDefault();

        var valorBusqueda = $(this).val();
        var valoroption = $("#cantidad_mostrar_porpagar").val();
        //console.log(valorBusqueda);

        if (valorBusqueda != "") {
            lista_cuentas_por_pagar(valorBusqueda, 1, valoroption);
        } else {
            lista_cuentas_por_pagar('', 1, valoroption);
        }
    });

    $("body").on("click", "#paginador_por_pagar li a", function (e) {
        e.preventDefault();
        valorhref = $(this).attr("href");
        valorBuscar = $("input[name=busquedaPago]").val();
        valoroption = $("#cantidad_mostrar_porpagar").val();
        //console.log(valorhref);
        lista_cuentas_por_pagar(valorBuscar, valorhref, valoroption);
    });

    $("#cantidad_mostrar_porpagar").change(function () {
        valoroption = $(this).val();
        //console.log(valoroption);
        valorBuscar = $("input[name=busquedaPago]").val();
        //console.log(valorBuscar);
        lista_cuentas_por_pagar(valorBuscar, 1, valoroption);
    });

    //Buscar Proveedor
    $('#nom_proveedor').keyup(function (e) {
        e.preventDefault();

        var proveedor = $(this).val();
        var action = 'searchProveedor';

        $.ajax({
            url: 'ajax.php',
            type: "POST",
            async: true,
            data: { action: action, proveedor: proveedor },

            success: function (response) {
                //console.log(response);
                if (response == 0) {
                    $('#idproveedor').val('');
                    $('#con_proveedor').val('');
                    $('#tel_proveedor').val('');
                    $('#dir_proveedor').val('');
                    //Mostrar boton agregar
                    $('.btn_new_proveedor').slideDown();
                } else {
                    var data = $.parseJSON(response);
                    $('#idproveedor').val(data.codproveedor);
                    $('#con_proveedor').val(data.contacto);
                    $('#tel_proveedor').val(data.telefono);
                    $('#dir_proveedor').val(data.direccion);
                    //Quitar boton agregar
                    $('.btn_new_proveedor').slideUp();

                    //Bloque campos
                    $('#con_proveedor').attr('disabled', 'disabled');
                    $('#tel_proveedor').attr('disabled', 'disabled');
                    $('#dir_proveedor').attr('disabled', 'disabled');

                    //Ocultar boton guardar
                    $('#div_registro_proveedor').slideUp();
                }
            },
            error: function (error) {
            }
        });
    });

    //Activa compo par registrar proveedor
    $('.btn_new_proveedor').click(function (e) {
        e.preventDefault();
        $('#con_proveedor').removeAttr('disabled');
        $('#tel_proveedor').removeAttr('disabled');
        $('#dir_proveedor').removeAttr('disabled');

        $('#div_registro_cliente').slideDown();
    });

    //Crear proveedor - compras
    $('#form_new_proveedor_compra').submit(function (e) {
        e.preventDefault();

        if ($('#con_proveedor').attr('disabled')) {
            return false;
        }

        if ($('#tel_proveedor').attr('disabled')) {
            return false;
        }

        if ($('#dir_proveedor').attr('disabled')) {
            return false;
        }



        $.ajax({
            url: 'ajax.php',
            type: "POST",
            async: true,
            data: $('#form_new_proveedor_compra').serialize(),

            success: function (response) {
                if (response != 'error') {
                    //Agregar id al input hidden
                    $('#idproveedor').val(response);
                    //Bloque campos
                    $('#con_proveedor').attr('disabled', 'disabled');
                    $('#tel_proveedor').attr('disabled', 'disabled');
                    $('#dir_proveedor').attr('disabled', 'disabled');

                    //Ocultar boton gagregar
                    $('.btn_new_cliente').slideUp();
                    //Ocultar boton guardar
                    $('#div_registro_cliente').slideUp();
                }
            },
            error: function (error) {
            }
        });
    });

}); //End ready

function generarReporteProducto(desde, hasta, busqueda) {
    var ancho = 1000;
    var alto = 800;
    //Calcularposicion x,y para centrar la ventana
    var x = parseInt((window.screen.width / 2) - (ancho / 2));
    var y = parseInt((window.screen.height / 2) - (alto / 2));

    $url = 'factura/generarReporteProducto.php?fecha_de=' + desde + '&fecha_a=' + hasta + '&busqueda=' + busqueda;
    window.open($url, "Factura", "left=" + x + ",top=" + y + ",height=" + alto + ",width=" + ancho + ",scrollbar=si,location=no,resizable=si,menubar=no");
}

function generarReportePDF_rango_compra(desde, hasta, busqueda) {
    var ancho = 1000;
    var alto = 800;
    //Calcularposicion x,y para centrar la ventana
    var x = parseInt((window.screen.width / 2) - (ancho / 2));
    var y = parseInt((window.screen.height / 2) - (alto / 2));

    $url = 'factura/generaReporteRango_compra.php?fecha_de=' + desde + '&fecha_a=' + hasta + '&busqueda=' + busqueda;
    window.open($url, "Factura", "left=" + x + ",top=" + y + ",height=" + alto + ",width=" + ancho + ",scrollbar=si,location=no,resizable=si,menubar=no");
}

function generarReportePDF_compra(pagina, busqueda) {
    var ancho = 1000;
    var alto = 800;
    //Calcularposicion x,y para centrar la ventana
    var x = parseInt((window.screen.width / 2) - (ancho / 2));
    var y = parseInt((window.screen.height / 2) - (alto / 2));

    $url = 'factura/generaReporte_compra.php?pagina=' + pagina + '&busqueda=' + busqueda;
    window.open($url, "Factura", "left=" + x + ",top=" + y + ",height=" + alto + ",width=" + ancho + ",scrollbar=si,location=no,resizable=si,menubar=no");
}

function generarReportePDF_mov(pagina, busqueda) {
    var ancho = 1000;
    var alto = 800;
    //Calcularposicion x,y para centrar la ventana
    var x = parseInt((window.screen.width / 2) - (ancho / 2));
    var y = parseInt((window.screen.height / 2) - (alto / 2));

    $url = 'factura/generarReportePDF_mov.php?pagina=' + pagina + '&busqueda=' + busqueda;
    window.open($url, "Factura", "left=" + x + ",top=" + y + ",height=" + alto + ",width=" + ancho + ",scrollbar=si,location=no,resizable=si,menubar=no");
}

function generarReportePDF_rango_mov(desde, hasta, busqueda) {
    var ancho = 1000;
    var alto = 800;
    //Calcularposicion x,y para centrar la ventana
    var x = parseInt((window.screen.width / 2) - (ancho / 2));
    var y = parseInt((window.screen.height / 2) - (alto / 2));

    $url = 'factura/generaReporteRango_mov.php?fecha_de=' + desde + '&fecha_a=' + hasta + '&busqueda=' + busqueda;
    window.open($url, "Factura", "left=" + x + ",top=" + y + ",height=" + alto + ",width=" + ancho + ",scrollbar=si,location=no,resizable=si,menubar=no");
}

function generarReportePDF_mov_pagar(pagina, busqueda) {
    var ancho = 1000;
    var alto = 800;
    //Calcularposicion x,y para centrar la ventana
    var x = parseInt((window.screen.width / 2) - (ancho / 2));
    var y = parseInt((window.screen.height / 2) - (alto / 2));

    $url = 'factura/generarReportePDF_mov_pagar.php?pagina=' + pagina + '&busqueda=' + busqueda;
    window.open($url, "Factura", "left=" + x + ",top=" + y + ",height=" + alto + ",width=" + ancho + ",scrollbar=si,location=no,resizable=si,menubar=no");
}

function generarReportePDF_rango_mov_pagar(desde, hasta, busqueda) {
    var ancho = 1000;
    var alto = 800;
    //Calcularposicion x,y para centrar la ventana
    var x = parseInt((window.screen.width / 2) - (ancho / 2));
    var y = parseInt((window.screen.height / 2) - (alto / 2));

    $url = 'factura/generaReporteRango_mov_pagar.php?fecha_de=' + desde + '&fecha_a=' + hasta + '&busqueda=' + busqueda;
    window.open($url, "Factura", "left=" + x + ",top=" + y + ",height=" + alto + ",width=" + ancho + ",scrollbar=si,location=no,resizable=si,menubar=no");
}

function generarReportePDF(pagina, busqueda, producto) {
    var ancho = 1000;
    var alto = 800;
    //Calcularposicion x,y para centrar la ventana
    var x = parseInt((window.screen.width / 2) - (ancho / 2));
    var y = parseInt((window.screen.height / 2) - (alto / 2));

    $url = 'factura/generaReporte.php?pagina=' + pagina + '&busqueda=' + busqueda + '&producto=' + producto;
    window.open($url, "Factura", "left=" + x + ",top=" + y + ",height=" + alto + ",width=" + ancho + ",scrollbar=si,location=no,resizable=si,menubar=no");
}

function generarReportePDF_rango(desde, hasta, busqueda, producto) {
    var ancho = 1000;
    var alto = 800;
    //Calcularposicion x,y para centrar la ventana
    var x = parseInt((window.screen.width / 2) - (ancho / 2));
    var y = parseInt((window.screen.height / 2) - (alto / 2));

    $url = 'factura/generaReporteRango.php?fecha_de=' + desde + '&fecha_a=' + hasta + '&busqueda=' + busqueda + '&producto=' + producto;
    window.open($url, "Factura", "left=" + x + ",top=" + y + ",height=" + alto + ",width=" + ancho + ",scrollbar=si,location=no,resizable=si,menubar=no");
}

function generarReportePDF_tipo(pagina, busqueda, tipo_pago, producto) {
    var ancho = 1000;
    var alto = 800;
    var x = parseInt((window.screen.width / 2) - (ancho / 2));
    var y = parseInt((window.screen.height / 2) - (alto / 2));

    $url = 'factura/generaReporte.php?pagina=' + pagina + '&busqueda=' + busqueda + '&tipo_pago=' + tipo_pago + '&producto=' + producto;
    window.open($url, "Factura", "left=" + x + ",top=" + y + ",height=" + alto + ",width=" + ancho + ",scrollbar=si,location=no,resizable=si,menubar=no");
}

function generarReportePDF_rango_tipo(desde, hasta, busqueda, tipo_pago, producto) {
    var ancho = 1000;
    var alto = 800;
    var x = parseInt((window.screen.width / 2) - (ancho / 2));
    var y = parseInt((window.screen.height / 2) - (alto / 2));

    $url = 'factura/generaReporteRango.php?fecha_de=' + desde + '&fecha_a=' + hasta + '&busqueda=' + busqueda + '&tipo_pago=' + tipo_pago + '&producto=' + producto;
    window.open($url, "Factura", "left=" + x + ",top=" + y + ",height=" + alto + ",width=" + ancho + ",scrollbar=si,location=no,resizable=si,menubar=no");
}

function descargarReportePDF(pagina, busqueda, producto, tipo_pago) {
    var url = 'factura/generaReporte.php?pagina=' + pagina + '&busqueda=' + busqueda + '&producto=' + producto + '&tipo_pago=' + tipo_pago + '&download=true';
    window.location.href = url;
}

function descargarReportePDF_rango(desde, hasta, busqueda, producto, tipo_pago) {
    var url = 'factura/generaReporteRango.php?fecha_de=' + desde + '&fecha_a=' + hasta + '&busqueda=' + busqueda + '&producto=' + producto + '&tipo_pago=' + tipo_pago + '&download=true';
    window.location.href = url;
}

function descargarReporteExcel(pagina, busqueda, producto, tipo_pago) {
    var url = 'factura/generaReporteExcel.php?pagina=' + pagina + '&busqueda=' + busqueda + '&producto=' + producto + '&tipo_pago=' + tipo_pago;
    window.location.href = url;
}

function descargarReporteExcel_rango(desde, hasta, busqueda, producto, tipo_pago) {
    var url = 'factura/generaReporteExcel.php?fecha_de=' + desde + '&fecha_a=' + hasta + '&busqueda=' + busqueda + '&producto=' + producto + '&tipo_pago=' + tipo_pago;
    window.location.href = url;
}

function descargarReporteExcel_compra(pagina, busqueda) {
    var url = 'factura/generaReporteExcel_compra.php?pagina=' + pagina + '&busqueda=' + busqueda;
    window.location.href = url;
}

function descargarReporteExcel_rango_compra(desde, hasta, busqueda) {
    var url = 'factura/generaReporteExcel_compra.php?fecha_de=' + desde + '&fecha_a=' + hasta + '&busqueda=' + busqueda;
    window.location.href = url;
}

function generarReportePDF_estadoR(desde, hasta) {
    var ancho = 1000;
    var alto = 800;
    //Calcularposicion x,y para centrar la ventana
    var x = parseInt((window.screen.width / 2) - (ancho / 2));
    var y = parseInt((window.screen.height / 2) - (alto / 2));

    $url = 'factura/generaReporteEstadoR.php?fecha_de=' + desde + '&fecha_a=' + hasta;
    window.open($url, "Factura", "left=" + x + ",top=" + y + ",height=" + alto + ",width=" + ancho + ",scrollbar=si,location=no,resizable=si,menubar=no");
}


function validPass() {
    var passNuevo = $('#txtNewPassUser').val();
    var confirmPassNuevo = $('#txtPassConfirm').val();
    if (passNuevo != confirmPassNuevo) {
        $('.alertChangePass').html('<p style="color:red;">Las contraseñas no son iguales.</p>');
        $('.alertChangePass').slideDown();
        return false;
    }

    if (passNuevo.length < 4) {
        $('.alertChangePass').html('<p style="color:red;">La contraseña debe ser de 4 caracteres como mínimo.</p>');
        $('.alertChangePass').slideDown();
        return false;
    }
    $('.alertChangePass').html('');
    $('.alertChangePass').slideDown();
}

//Anular factura
function anularFactura() {
    var noFactura = $('#no_factura').val();
    var action = 'anularFactura';

    $.ajax({
        url: 'ajax.php',
        type: "POST",
        async: true,
        data: { action: action, noFactura: noFactura },

        success: function (response) {
            if (response == 'error') {
                $('.alertAddProduct').html('<p style="color:red;">Error al anular la venta.</p>');
            } else {
                $('#row_' + noFactura + ' .estado').html('<span class="anulada">Anulada</span>');
                $('#form_anular_factura .btn_ok').remove();
                $('#row_' + noFactura + ' .div_factura').html('<button type="button" class="btn_anular inactive" ><i class="fas fa-ban"></i></button>');
                $('.alertAddProduct').html('<p>Venta anulada.</p>');
                setTimeout(function () {
                    coloseModal();
                }, 1500);
            }
        },
        error: function (error) {

        }
    });
}

//Anular compra
function anularFacturaCompra() {
    var noFactura = $('#no_factura').val();
    var action = 'anularFactCompra';

    $.ajax({
        url: 'ajax.php',
        type: "POST",
        async: true,
        data: { action: action, noFactura: noFactura },

        success: function (response) {
            if (response == 'error') {
                $('.alertAddProduct').html('<p style="color:red;">Error al anular la compra.</p>');
            } else {
                $('#row_' + noFactura + ' .estado').html('<span class="anulada">Anulada</span>');
                $('#form_anular_factura .btn_ok').remove();
                $('#row_' + noFactura + ' .div_factura').html('<button type="button" class="btn_anular inactive" ><i class="fas fa-ban"></i></button>');
                $('.alertAddProduct').html('<p>Compra anulada correctamente.</p>');
                setTimeout(function () {
                    coloseModal();
                }, 1500);
            }
        },
        error: function (error) {

        }
    });
}

function generarPDF_compra(cliente, factura) {
    var ancho = 1000;
    var alto = 800;
    //Calcularposicion x,y para centrar la ventana
    var x = parseInt((window.screen.width / 2) - (ancho / 2));
    var y = parseInt((window.screen.height / 2) - (alto / 2));

    $url = 'factura/generaCompra.php?cl=' + cliente + '&f=' + factura;
    window.open($url, "Factura", "left=" + x + ",top=" + y + ",height=" + alto + ",width=" + ancho + ",scrollbar=si,location=no,resizable=si,menubar=no");
}

function generarPDF(cliente, factura) {
    var ancho = 1000;
    var alto = 800;
    //Calcularposicion x,y para centrar la ventana
    var x = parseInt((window.screen.width / 2) - (ancho / 2));
    var y = parseInt((window.screen.height / 2) - (alto / 2));

    $url = 'factura/generaFactura.php?cl=' + cliente + '&f=' + factura;
    window.open($url, "Factura", "left=" + x + ",top=" + y + ",height=" + alto + ",width=" + ancho + ",scrollbar=si,location=no,resizable=si,menubar=no");
}

function generarPDFTicket(cliente, factura) {
    var ancho = 1000;
    var alto = 800;
    //Calcularposicion x,y para centrar la ventana
    var x = parseInt((window.screen.width / 2) - (ancho / 2));
    var y = parseInt((window.screen.height / 2) - (alto / 2));

    $url = 'factura/generaTicket.php?cl=' + cliente + '&f=' + factura;
    window.open($url, "Factura", "left=" + x + ",top=" + y + ",height=" + alto + ",width=" + ancho + ",scrollbar=si,location=no,resizable=si,menubar=no");
}

function del_product_detalle(correlativo) {
    var action = 'delProductoDetalle';
    var descuento = $('#descuneto_venta').val();
    var id_detalle = correlativo;

    if (descuento == '') {
        descuento = 0;
    }

    $.ajax({
        url: 'ajax.php',
        type: "POST",
        async: true,
        data: { action: action, id_detalle: id_detalle, descuento: descuento },

        success: function (response) {
            //console.log(response);
            if (response != 'error') {
                var info = JSON.parse(response);

                $('#detalle_venta').html(info.detalle);
                $('#detalle_totales').html(info.totales);
                if (info.total_con_desc_raw !== undefined) {
                    window.totalVentaGlobal = info.total_con_desc_raw;
                }

                $('#txt_cod_producto').val('');
                $('#txt_descripcion').html('-');
                $('#txt_existencia').html('-');
                $('#txt_cant_producto').val('0');
                $('#txt_precio').html('0.00');
                $('#txt_precio_total').html('0.00');
                $('#txt_cod_producto').focus();

                //Bloquear cantidad
                $('#txt_cant_producto').attr('disabled', 'disabled');

                //Ocultar boton agregar
                $('#add_product_venta').slideUp();

            } else {
                $('#detalle_venta').html('');
                $('#detalle_totales').html('');
            }
            viewProcesar();

        },
        error: function (error) {

        }
    });

}

//Mostrar/Ocultar boton procesar
function viewProcesar() {
    if ($('#detalle_venta tr').length > 0) {
        $('#btn_facturar_venta').show();
        $('#btn_anular_venta').show();
    } else {
        $('#btn_facturar_venta').hide();
        $('#btn_anular_venta').hide();
    }
}

//Mostrar/Ocultar boton procesar
function viewProcesarCompra() {
    if ($('#detalle_venta_compra tr').length > 0) {
        $('#btn_facturar_compra').show();
        $('#btn_anular_compra').show();
    } else {
        $('#btn_facturar_compra').hide();
        $('#btn_anular_compra').hide();
    }
}

function serchForDetalle(id, descuento) {
    var action = 'serchForDetalle';
    var descuento = descuento;
    var user = id;
    if (descuento == '') {
        descuento = 0;
    }

    $.ajax({
        url: 'ajax.php',
        type: "POST",
        async: true,
        data: { action: action, user: user, descuento: descuento },

        success: function (response) {
            //console.log(response);
            if (response != 'error') {
                var info = JSON.parse(response);
                $('#detalle_venta').html(info.detalle);
                $('#detalle_totales').html(info.totales);

                // Sincronizar total con descuento real para cálculos globales
                if (info.total_con_desc_raw !== undefined) {
                    window.totalVentaGlobal = info.total_con_desc_raw;
                }
            } else {
                console.log('no data');

            }
            viewProcesar();
        },
        error: function (error) {

        }
    });
}

function getUrl() {
    var loc = window.location;
    var pathName = loc.pathname.substring(0, loc.pathname.lastIndexOf('/') + 1);
    return loc.href.substring(0, loc.href.length - ((loc.pathname + loc.search + loc.hash).length - pathName.length));
}

function sendDataProduct() {

    $('.alertAddProduct').html('');

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: $('#form_add_product').serialize(),

        success: function (response) {
            if (response == 'error') {
                $('alertAddProduct').html('<p style="color: red;">Error al agregar el producto.</p>');
            } else {
                var info = JSON.parse(response);
                $('.row' + info.producto_id + ' .celPrecio').html(info.nuevo_precio);
                $('.row' + info.producto_id + ' .celExistencia').html(info.nueva_existencia);
                $('#txtCantidad').val('');
                $('#txtPrecio').val('');
                $('.alertAddProduct').html('<p>Producto guardado correctamente.</p>');
                setTimeout(function () {
                    coloseModal();
                }, 1500);
            }

        },

        error: function (error) {
            console.log(error);
        }

    });

}

//Eliminar Producto
function delProduct() {

    var pr = $('#producto_id').val();
    $('.alertAddProduct').html('');

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: $('#form_del_product').serialize(),

        success: function (response) {
            if (response == 'error') {
                $('alertAddProduct').html('<p style="color: red;">Error al eliminar el producto.</p>');
            } else {
                $('.row' + pr).remove();
                $('#form_del_product .btn_ok').remove();
                $('.alertAddProduct').html('<p>Producto eliminado correctamente.</p>');
                setTimeout(function () {
                    coloseModal();
                }, 1500);
            }

        },

        error: function (error) {
            console.log(error);
        }

    });

}

function coloseModal() {

    $('.alertAddProduct').html('');
    $('#txtCantidad').val('');
    $('#txtPrecio').val('');
    $('.modal').fadeOut();
    $('.modalBuscarCl').fadeOut();
    $('.modalBuscarPr').fadeOut();
    $('.modalBuscarPrCompra').fadeOut();
}


function agregarCliente(id) {

    var cl = id;
    var action = 'searchCliente';

    $.ajax({
        url: 'ajax.php',
        type: "POST",
        async: true,
        data: { action: action, cliente: cl },

        success: function (response) {

            var data = $.parseJSON(response);
            $('#nit_cliente').val(data.nit);

        },
        error: function (error) {
        }
    });

    $('.modalBuscarCl').fadeOut();
    $('#nit_cliente').focus();


}

function serchForDetalleCli(pagina, busqueda) {

    var pagina = pagina;
    $.ajax({
        url: 'action/data_cliente_2.php',
        type: "POST",
        data: { pagina: pagina, busqueda: busqueda },

        success: function (response) {
            if (response != 'error') {
                var info = JSON.parse(response);
                $('#dataCliente').html(info.detalle);
                $('#paginadorCliente').html(info.totales);

            } else {
                $('#dataCliente').html('<table>' +
                    '<tr>' +
                    '<th>Nit</th>' +
                    '<th>Nombre</th>' +
                    '<th>Teléfono</th>' +
                    '<th>Dirección</th>' +
                    '<th>Acción</th>' +
                    '</tr>' +
                    '<tbody>' +
                    '<tr><td colspan="7">No se encontraron concidencias :(</td></tr>' +
                    '</tbody>');
                $('#paginadorCliente').html('');
                //console.log('no data');

            }

        },
        error: function (error) {

        }
    });
}

function serchForDetalleProd(busquedaProd, pagina) {
    var pagina = pagina;
    $.ajax({
        url: 'action/data_producto.php',
        type: "POST",
        data: { pagina: pagina, busquedaProd: busquedaProd },

        success: function (response) {
            //console.log(response);
            if (response != 'error') {
                var info = JSON.parse(response);
                $('#dataProd').html(info.detalle);
                $('#paginadorProd').html(info.totales);

            } else {
                $('#dataProd').html('<table>' +
                    '<tr>' +
                    '<th>Código</th>' +
                    '<th>Descripción</th>' +
                    '<th>Existencia</th>' +
                    '<th>Precio</th>' +
                    '<th>Foto</th>' +
                    '<th>Cantidad</th>' +
                    '<th>Acción</th>' +
                    '</tr>' +
                    '<tbody>' +
                    '<tr><td colspan="7">No se encontraron concidencias :(</td></tr>' +
                    '</tbody>');
                $('#paginadorProd').html('');
                //console.log('no data');

            }

        },
        error: function (error) {

        }

    });
}

function agregarProducto(codigo) {
    //$('.alertAddProduct').html('');
    var codproducto = codigo;
    var existencia = parseInt($('#txt_existencia_venta').val());
    var cantidad = $('#txt_cant_producto_venta').val();

    if (cantidad > existencia) {
        alert('No hay inventarios suficiente.');
        return false;
    }

    $.ajax({
        url: 'ajax.php',
        type: "POST",
        async: true,
        data: $('#form_del_product').serialize(),
        success: function (response) {
            //console.log(response);
            if (response != 'error') {
                var info = JSON.parse(response);
                $('#detalle_venta').html(info.detalle);
                $('#detalle_totales').html(info.totales);
                if (info.total_con_desc_raw !== undefined) {
                    window.totalVentaGlobal = info.total_con_desc_raw;
                }

                $('#busquedaProd').val('');
                $('#busquedaProd').focus();
                $('.modal').fadeOut();

            } else {
                alert('No se encontro el producto');
                console.log('no data');

            }
            viewProcesar();
        },
        error: function (error) {
        }
    });


    //location.reload();
    //$('.modalBuscarPr').fadeIn();
}
//Lista de clientes
function listaCliente(busqueda, pagina, cantidad) {

    var pagina = pagina;
    $.ajax({
        url: 'action/data_cliente.php',
        type: "POST",
        data: { pagina: pagina, busqueda: busqueda, cantidad: cantidad },

        success: function (response) {
            //console.log(response);
            if (response != 'error') {
                var info = JSON.parse(response);
                $('#listaCliente').html(info.detalle);
                $('#paginadorClient').html(info.totales);

            } else {
                $('#listaCliente').html('<table>' +
                    '<tr>' +
                    '<th>Nit</th>' +
                    '<th>Nombre</th>' +
                    '<th>Teléfono</th>' +
                    '<th>Dirección</th>' +
                    '<th>Acción</th>' +
                    '</tr>' +
                    '<tbody>' +
                    '<tr><td colspan="7">No se encontraron concidencias :(</td></tr>' +
                    '</tbody>');
                $('#paginadorClient').html('');
                //console.log('no data');

            }

        },
        error: function (error) {

        }
    });
}

//Modal editar cliente//
function editarCliente(id) {
    var cliente = id;
    var action = 'editarCliente';

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: { action: action, cliente: cliente },

        success: function (response) {
            //console.log(response);            
            if (response != 'error') {
                var info = JSON.parse(response);

                $('.bodyModal').html('<form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); actualizarCliente();">' +
                    '<input type="hidden" name="action" value="actualizarCliente">' +
                    '<h1><i class="fas fa-user" style="font-size: 45pt;"></i> <br> Actualizar cliente</h1>' +
                    '<input type="hidden" name="idCliente" value="' + info.idcliente + '">' +
                    '<input type="text" name="nitCliente" id="nitCliente" value="' + info.nit + '" placeholder="Ruc" required><br>' +
                    '<input type="text" name="nombreCliente" id="nombreCliente" value="' + info.nombre + '" placeholder="Nombre y apellidos" onkeypress="return soloLetras(event)" onpaste="return false" required><br>' +
                    '<input type="number" name="telefonoCliente" id="telefonoCliente" value="' + info.telefono + '" placeholder="Teléfono" min="0"><br>' +
                    '<input type="text" name="direccionCliente" id="direccionCliente" value="' + info.direccion + '" placeholder="Dirección"><br>' +
                    '<div class="alert alertAddProduct"></div>' +
                    '<a href="#" class="btn_ok closeModal" onclick="coloseModal(); "><i class="fas fa-ban"></i> Cerrar</a>' +
                    '<button type="submit" class="btn_new"><i class="fas fa-plus"></i> Guardar</button>' +
                    '</form>');
            }
        },

        error: function (error) {
            console.log(error);
        }

    });

    $('.modal').fadeIn();

}

function actualizarCliente() {

    $('.alertAddProduct').html('');
    var nit = $('#nitCliente').val();
    var nombre = $('#nombreCliente').val();
    var busquedaCli = $('#busquedaCliente').val();
    if (nit.length < 5) {
        $('.alertAddProduct').html('<p style="color:red;">El RUC debe ser de 5 caracteres como mínimo.</p>');
        $('.alertAddProduct').slideDown();
        return false;
    }
    if (nombre.length < 4) {
        $('.alertAddProduct').html('<p style="color:red;">El Nombre debe ser de 4 caracteres como mínimo.</p>');
        $('.alertAddProduct').slideDown();
        return false;
    }

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: $('#form_add_product').serialize(),

        success: function (response) {

            var info = JSON.parse(response);
            if (info.cod == '00') {
                $('.alertAddProduct').html('<p style="color:green;">' + info.msg + '</p>');
                $('#form_add_product')[0].reset();
                listaCliente(busquedaCli, 1, 10);
                setTimeout(function () {
                    coloseModal();
                }, 1500);
            } else {
                $('.alertAddProduct').html('<p style="color:red;">' + info.msg + '</p>');
            }
        },

        error: function (error) {
            console.log(error);
        }

    });
}

//Modal eliminar cliente//
function infoEliminarCliente(id) {
    var cliente = id;
    var action = 'editarCliente';

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: { action: action, cliente: cliente },

        success: function (response) {
            //console.log(response);            
            if (response != 'error') {
                var info = JSON.parse(response);

                $('.bodyModal').html('<form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); eliminarCliente();">' +
                    '<input type="hidden" name="action" value="eliminarCliente">' +
                    '<h1><i class="fas fa-user" style="font-size: 45pt;"></i> <br> Eliminar cliente</h1>' +
                    '<input type="hidden" name="cliente_id" id="cliente_id" value="' + info.idcliente + '">' +
                    '<p>¿Está seguro de eliminar el siguiente registro?</p>' +
                    '<h2 class="nameProducto">' + info.nombre + '</h2> <br>' +
                    '<h2 class="nameProducto">Ruc: ' + info.nit + '</h2> <br>' +
                    '<div class="alert alertAddProduct"></div>' +
                    '<a href="#" class="btn_ok closeModal" onclick="coloseModal(); "><i class="fas fa-ban"></i> Cerrar</a>' +
                    '<button type="submit" class="btn_new"><i class="fas fa-plus"></i> Eliminar</button>' +
                    '</form>');
            }
        },

        error: function (error) {
            console.log(error);
        }

    });

    $('.modal').fadeIn();

}

//Eliminar cliente
function eliminarCliente() {

    var cliente = $('#cliente_id').val();
    $('.alertAddProduct').html('');

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: $('#form_add_product').serialize(),

        success: function (response) {
            if (response == 'error') {
                $('alertAddProduct').html('<p style="color: red;">Error al eliminar el cliente.</p>');
            } else {
                $('.row' + cliente).remove();
                $('#form_add_product .btn_new').remove();
                $('.alertAddProduct').html('<p>Cliente eliminado correctamente.</p>');
                listaCliente('', 1, 10);
                setTimeout(function () {
                    coloseModal();
                }, 1500);
            }

        },

        error: function (error) {
            console.log(error);
        }

    });

}


//Registrar cliente
function nuevoCliente() {

    $('.alertAddProduct').html('');
    var nit = $('#nitCliente').val();
    var nombre = $('#nombreCliente').val();
    if (nit.length < 5) {
        $('.alertAddProduct').html('<p style="color:red;">El RUC debe ser de 5 caracteres como mínimo.</p>');
        $('.alertAddProduct').slideDown();
        return false;
    }
    if (nombre.length < 4) {
        $('.alertAddProduct').html('<p style="color:red;">El Nombre debe ser de 4 caracteres como mínimo.</p>');
        $('.alertAddProduct').slideDown();
        return false;
    }

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: $('#form_add_product').serialize(),

        success: function (response) {
            //console.log(response);           
            var info = JSON.parse(response);
            if (info.cod == '00') {
                $('.alertAddProduct').html('<p style="color:green;">' + info.msg + '</p>');
                $('#form_add_product')[0].reset();
                listaCliente('', 1, 10);
                setTimeout(function () {
                    coloseModal();
                }, 1500);
            } else {
                $('.alertAddProduct').html('<p style="color:red;">' + info.msg + '</p>');
            }

        },

        error: function (error) {
            console.log(error);
        }

    });

}

//Lista de usuarios
function listaUsuario(busqueda, pagina, cantidad) {

    var pagina = pagina;
    $.ajax({
        url: 'action/data_usuario.php',
        type: "POST",
        data: { pagina: pagina, busqueda: busqueda, cantidad: cantidad },

        success: function (response) {
            if (response != 'error') {
                var info = JSON.parse(response);
                $('#listaUsuario').html(info.detalle);
                $('#paginadorUsuario').html(info.totales);

            } else {
                $('#listaUsuario').html('<table>' +
                    '<tr>' +
                    '<th>ID</th>' +
                    '<th>Nombre</th>' +
                    '<th>Correo</th>' +
                    '<th>Usuario</th>' +
                    '<th>Rol</th>' +
                    '<th>Acciones</th>' +
                    '</tr>' +
                    '<tbody>' +
                    '<tr><td colspan="7">No se encontraron concidencias :(</td></tr>' +
                    '</tbody>');
                $('#paginadorUsuario').html('');
                //console.log('no data');

            }

        },
        error: function (error) {

        }
    });
}

//Modal editar Usuario
function editarUsuario(id) {
    var usuario = id;
    var action = 'editarUsuario';

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: { action: action, usuario: usuario },

        success: function (response) {
            //console.log(response);            
            if (response != 'error') {
                var info = JSON.parse(response);

                $('.bodyModal').html('<form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); actualizarUsuario();">' +
                    '<input type="hidden" name="action" value="actualizarUsuario">' +
                    '<h1><i class="fas fa-user" style="font-size: 45pt;"></i> <br> Actualizar usuario</h1>' +
                    '<input type="hidden" name="idUsuario" value="' + info.usuario.idusuario + '">' +
                    '<input type="text" name="nombreUsuario" id="nombreUsuario" value="' + info.usuario.nombre + '" placeholder="Nombre y apellidos" onkeypress="return soloLetras(event)" onpaste="return false" required><br>' +
                    '<input type="email" name="correoUsuario" id="correoUsuario" value="' + info.usuario.correo + '" placeholder="Correo electrónico" required><br>' +
                    '<input type="text" name="usuario" id="usuario" value="' + info.usuario.usuario + '" placeholder="Usuario" required><br>' +
                    '<input type="password" name="claveUsuario" id="claveUsuario" value="" placeholder="Clave"><br>' +
                    '<select name="rolUsuario" id="rolUsuario" required><option value="' + info.usuario.idrol + '">' + info.usuario.rol + '</option>' + info.rol + '</select><br>' +
                    '<div class="alert alertAddProduct"></div>' +
                    '<a href="#" class="btn_ok closeModal" onclick="coloseModal(); "><i class="fas fa-ban"></i> Cerrar</a>' +
                    '<button type="submit" class="btn_new"><i class="fas fa-plus"></i> Guardar</button>' +
                    '</form>');
            }
        },

        error: function (error) {
            console.log(error);
        }

    });

    $('.modal').fadeIn();

}

//Actualizar usuarios
function actualizarUsuario() {
    $('.alertAddProduct').html('');
    var nombre = $('#nombreUsuario').val();
    var usuario = $('#usuario').val();
    var clave = $('#claveUsuario').val();
    var busquedaUsu = $('#busquedaUsuario').val();
    if (nombre.length < 4) {
        $('.alertAddProduct').html('<p style="color:red;">El Nombre debe ser de 4 caracteres como mínimo.</p>');
        $('.alertAddProduct').slideDown();
        return false;
    }
    if (usuario.length < 4) {
        $('.alertAddProduct').html('<p style="color:red;">El Usuario debe ser de 4 caracteres como mínimo.</p>');
        $('.alertAddProduct').slideDown();
        return false;
    }

    if ($("#correoUsuario").val().indexOf('@', 0) == -1 || $("#correoUsuario").val().indexOf('.', 0) == -1) {
        $('.alertAddProduct').html('<p style="color:red;">El correo electrónico no es correcto.</p>');
        return false;
    }

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: $('#form_add_product').serialize(),

        success: function (response) {
            //console.log(response);
            var info = JSON.parse(response);
            if (info.cod == '00') {
                $('.alertAddProduct').html('<p style="color:green;">' + info.msg + '</p>');
                $('#form_add_product')[0].reset();
                listaUsuario(busquedaUsu, 1, 10);
                setTimeout(function () {
                    coloseModal();
                }, 1500);
            } else {
                $('.alertAddProduct').html('<p style="color:red;">' + info.msg + '</p>');
            }
        },

        error: function (error) {
            console.log(error);
        }

    });
}

//Modal eliminar usuario//
function infoEliminarUsuario(id) {
    var usuario = id;
    var action = 'editarUsuario';

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: { action: action, usuario: usuario },

        success: function (response) {
            //console.log(response);            
            if (response != 'error') {
                var info = JSON.parse(response);

                $('.bodyModal').html('<form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); eliminarUsuario();">' +
                    '<input type="hidden" name="action" value="eliminarUsuario">' +
                    '<h1><i class="fas fa-user" style="font-size: 45pt;"></i> <br> Eliminar usuario</h1>' +
                    '<input type="hidden" name="usuario_id" id="usuario_id" value="' + info.usuario.idusuario + '">' +
                    '<p>¿Está seguro de eliminar el siguiente registro?</p>' +
                    '<h2 class="nameProducto">' + info.usuario.nombre + '</h2> <br>' +
                    '<h2 class="nameProducto">' + info.usuario.usuario + '</h2> <br>' +
                    '<h2 class="nameProducto">' + info.usuario.rol + '</h2> <br>' +
                    '<div class="alert alertAddProduct"></div>' +
                    '<a href="#" class="btn_ok closeModal" onclick="coloseModal(); "><i class="fas fa-ban"></i> Cerrar</a>' +
                    '<button type="submit" class="btn_new"><i class="fas fa-plus"></i> Eliminar</button>' +
                    '</form>');
            }
        },

        error: function (error) {
            console.log(error);
        }

    });

    $('.modal').fadeIn();

}

//Eliminar Usuario
function eliminarUsuario() {

    var usuario = $('#usuario_id').val();
    $('.alertAddProduct').html('');

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: $('#form_add_product').serialize(),

        success: function (response) {
            if (response == 'error') {
                $('alertAddProduct').html('<p style="color: red;">Error al eliminar el usuario.</p>');
            } else {
                $('.row' + usuario).remove();
                $('#form_add_product .btn_new').remove();
                $('.alertAddProduct').html('<p>Usuario eliminado correctamente.</p>');
                listaUsuario('', 1, 10);
                setTimeout(function () {
                    coloseModal();
                }, 1500);
            }

        },

        error: function (error) {
            console.log(error);
        }

    });

}


//Registrar usuario
function nuevoUsuario() {

    $('.alertAddProduct').html('');
    var nombre = $('#nombreUsuario').val();
    var usuario = $('#usuario').val();
    var clave = $('#claveUsuario').val();
    if (nombre.length < 4) {
        $('.alertAddProduct').html('<p style="color:red;">El Nombre debe ser de 4 caracteres como mínimo.</p>');
        $('.alertAddProduct').slideDown();
        return false;
    }
    if (usuario.length < 4) {
        $('.alertAddProduct').html('<p style="color:red;">El Usuario debe ser de 4 caracteres como mínimo.</p>');
        $('.alertAddProduct').slideDown();
        return false;
    }
    if (clave.length < 4) {
        $('.alertAddProduct').html('<p style="color:red;">La contraseña debe ser de 4 caracteres como mínimo.</p>');
        $('.alertAddProduct').slideDown();
        return false;
    }

    if ($("#correoUsuario").val().indexOf('@', 0) == -1 || $("#correoUsuario").val().indexOf('.', 0) == -1) {
        $('.alertAddProduct').html('<p style="color:red;">El correo electrónico no es correcto.</p>');
        return false;
    }


    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: $('#form_add_product').serialize(),

        success: function (response) {
            var info = JSON.parse(response);
            if (info.cod == '00') {
                $('.alertAddProduct').html('<p style="color:green;">' + info.msg + '</p>');
                $('#form_add_product')[0].reset();
                listaUsuario('', 1, 10);
                setTimeout(function () {
                    coloseModal();
                }, 1500);
            } else {
                $('.alertAddProduct').html('<p style="color:red;">' + info.msg + '</p>');
            }


        },

        error: function (error) {
            console.log(error);
        }

    });

}

//Lista de proveedor
function listaProveedor(busqueda, pagina, cantidad) {

    var pagina = pagina;
    $.ajax({
        url: 'action/data_proveedor.php',
        type: "POST",
        data: { pagina: pagina, busqueda: busqueda, cantidad: cantidad },

        success: function (response) {
            //console.log(response);
            if (response != 'error') {
                var info = JSON.parse(response);
                //console.log(response);
                $('#listaProveedor').html(info.detalle);
                $('#paginadorProveedor').html(info.totales);

            } else {
                $('#listaProveedor').html('<table>' +
                    '<tr>' +
                    '<th>ID</th>' +
                    '<th>Proveedor</th>' +
                    '<th>Contacto</th>' +
                    '<th>Teléfono</th>' +
                    '<th>Dirección</th>' +
                    '<th>Fecha</th>' +
                    '<th>Acciones</th>' +
                    '</tr>' +
                    '<tbody>' +
                    '<tr><td colspan="7">No se encontraron concidencias :(</td></tr>' +
                    '</tbody>');
                $('#paginadorProveedor').html('');
                //console.log('no data');

            }

        },
        error: function (error) {

        }
    });
}

//Modal editar proveedor//
function editarProveedor(id) {
    var proveedor = id;
    var action = 'editarProveedor';

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: { action: action, proveedor: proveedor },

        success: function (response) {
            //console.log(response);            
            if (response != 'error') {
                var info = JSON.parse(response);

                $('.bodyModal').html('<form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); actualizarProveedor();">' +
                    '<input type="hidden" name="action" value="actualizarProveedor">' +
                    '<h1><i class="fas fa-user" style="font-size: 45pt;"></i> <br> Actualizar proveedor</h1>' +
                    '<input type="hidden" name="idProveedor" value="' + info.codproveedor + '">' +
                    '<input type="text" name="nombreProveedor" id="nombreProveedor" value="' + info.proveedor + '" placeholder="Nombre de proveedor" onkeypress="return soloLetras(event)" onpaste="return false" required><br>' +
                    '<input type="text" name="nombreContacto" id="nombreContacto" value="' + info.contacto + '" placeholder="Nombre del contacto" onkeypress="return soloLetras(event)" onpaste="return false" required><br>' +
                    '<input type="number" name="telefonoProveedor" id="telefonoProveedor" value="' + info.telefono + '" placeholder="Teléfono" min="0" required><br>' +
                    '<input type="text" name="direccionProveedor" id="direccionProveedor" value="' + info.direccion + '" placeholder="Dirección" required><br>' +
                    '<div class="alert alertAddProduct"></div>' +
                    '<a href="#" class="btn_ok closeModal" onclick="coloseModal(); "><i class="fas fa-ban"></i> Cerrar</a>' +
                    '<button type="submit" class="btn_new"><i class="fas fa-plus"></i> Guardar</button>' +
                    '</form>');
            }
        },

        error: function (error) {
            console.log(error);
        }

    });

    $('.modal').fadeIn();

}

//Actualizar proveedor
function actualizarProveedor() {

    $('.alertAddProduct').html('');
    var nombre = $('#nombreProveedor').val();
    var contacto = $('#nombreContacto').val();
    var telefono = $('#telefonoProveedor').val();
    var direccion = $('#direccionProveedor').val();
    var busquedaProv = $('#busquedaProveedor').val();
    if (nombre.length < 4) {
        $('.alertAddProduct').html('<p style="color:red;">El Nombre debe ser de 4 caracteres como mínimo.</p>');
        $('.alertAddProduct').slideDown();
        return false;
    }
    if (contacto.length < 4) {
        $('.alertAddProduct').html('<p style="color:red;">El contacto debe ser de 4 caracteres como mínimo.</p>');
        $('.alertAddProduct').slideDown();
        return false;
    }
    if (telefono.length < 8) {
        $('.alertAddProduct').html('<p style="color:red;">El Teléfono debe ser de 8 caracteres como mínimo.</p>');
        $('.alertAddProduct').slideDown();
        return false;
    }
    if (direccion.length < 4) {
        $('.alertAddProduct').html('<p style="color:red;">La Dirección debe ser de 4 caracteres como mínimo.</p>');
        $('.alertAddProduct').slideDown();
        return false;
    }

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: $('#form_add_product').serialize(),

        success: function (response) {

            var info = JSON.parse(response);
            if (info.cod == '00') {
                $('.alertAddProduct').html('<p style="color:green;">' + info.msg + '</p>');
                $('#form_add_product')[0].reset();
                listaProveedor(busquedaProv, 1, 10);
                setTimeout(function () {
                    coloseModal();
                }, 1500);
            } else {
                $('.alertAddProduct').html('<p style="color:red;">' + info.msg + '</p>');
            }
        },

        error: function (error) {
            console.log(error);
        }

    });
}

//Modal eliminar proveedor//
function infoEliminarProveedor(id) {
    var proveedor = id;
    var action = 'editarProveedor';

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: { action: action, proveedor: proveedor },

        success: function (response) {
            //console.log(response);            
            if (response != 'error') {
                var info = JSON.parse(response);

                $('.bodyModal').html('<form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); eliminarProveedor();">' +
                    '<input type="hidden" name="action" value="eliminarProveedor">' +
                    '<h1><i class="fas fa-user" style="font-size: 45pt;"></i> <br> Eliminar proveedor</h1>' +
                    '<input type="hidden" name="proveedor_id" id="proveedor_id" value="' + info.codproveedor + '">' +
                    '<p>¿Está seguro de eliminar el siguiente registro?</p>' +
                    '<h2 class="nameProducto">' + info.proveedor + '</h2> <br>' +
                    '<h2 class="nameProducto">' + info.contacto + '</h2> <br>' +
                    '<div class="alert alertAddProduct"></div>' +
                    '<a href="#" class="btn_ok closeModal" onclick="coloseModal(); "><i class="fas fa-ban"></i> Cerrar</a>' +
                    '<button type="submit" class="btn_new"><i class="fas fa-plus"></i> Eliminar</button>' +
                    '</form>');
            }
        },

        error: function (error) {
            console.log(error);
        }

    });

    $('.modal').fadeIn();

}

//Eliminar proveedor
function eliminarProveedor() {

    var proveedor = $('#proveedor_id').val();
    $('.alertAddProduct').html('');

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: $('#form_add_product').serialize(),

        success: function (response) {
            if (response == 'error') {
                $('alertAddProduct').html('<p style="color: red;">Error al eliminar el proveedor.</p>');
            } else {
                $('.row' + proveedor).remove();
                $('#form_add_product .btn_new').remove();
                $('.alertAddProduct').html('<p>Proveedor eliminado correctamente.</p>');
                listaProveedor('', 1, 10);
                setTimeout(function () {
                    coloseModal();
                }, 1500);
            }

        },

        error: function (error) {
            console.log(error);
        }

    });

}


//Registrar proveedor
function nuevoProveedor() {

    $('.alertAddProduct').html('');
    var nombre = $('#nombreProveedor').val();
    var contacto = $('#nombreContacto').val();
    var telefono = $('#telefonoProveedor').val();
    var direccion = $('#direccionProveedor').val();
    if (nombre.length < 4) {
        $('.alertAddProduct').html('<p style="color:red;">El Nombre debe ser de 4 caracteres como mínimo.</p>');
        $('.alertAddProduct').slideDown();
        return false;
    }
    if (contacto.length < 4) {
        $('.alertAddProduct').html('<p style="color:red;">El contacto debe ser de 4 caracteres como mínimo.</p>');
        $('.alertAddProduct').slideDown();
        return false;
    }
    if (telefono.length < 8) {
        $('.alertAddProduct').html('<p style="color:red;">El Teléfono debe ser de 8 caracteres como mínimo.</p>');
        $('.alertAddProduct').slideDown();
        return false;
    }
    if (direccion.length < 4) {
        $('.alertAddProduct').html('<p style="color:red;">La Dirección debe ser de 4 caracteres como mínimo.</p>');
        $('.alertAddProduct').slideDown();
        return false;
    }
    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: $('#form_add_product').serialize(),

        success: function (response) {
            var info = JSON.parse(response);
            if (info.cod == '00') {
                $('.alertAddProduct').html('<p style="color:green;">' + info.msg + '</p>');
                $('#form_add_product')[0].reset();
                listaProveedor('', 1, 10);
                setTimeout(function () {
                    coloseModal();
                }, 1500);
            } else {
                $('.alertAddProduct').html('<p style="color:red;">' + info.msg + '</p>');
            }


        },

        error: function (error) {
            console.log(error);
        }

    });

}

//Lista de productos
function listaProductos(busqueda, pagina, cantidad) {

    var pagina = pagina;
    $.ajax({
        url: 'action/data_producto1.php',
        type: "POST",
        data: { pagina: pagina, busqueda: busqueda, cantidad: cantidad },

        success: function (response) {
            //console.log(response);
            if (response != 'error') {
                var info = JSON.parse(response);
                $('#listaProducto').html(info.detalle);
                $('#paginadorProducto').html(info.totales);

            } else {
                $('#listaProducto').html('<table>' +
                    '<tr>' +
                    '<th>Código</th>' +
                    '<th>Descripción</th>' +
                    '<th>Precio</th>' +
                    '<th>Existencia</th>' +
                    '<th>Proveedor</th>' +
                    '<th>Foto</th>' +
                    '<th>Acciones</th>' +
                    '</tr>' +
                    '<tbody>' +
                    '<tr><td colspan="7">No se encontraron concidencias :(</td></tr>' +
                    '</tbody>');
                $('#paginadorProducto').html('');
                //console.log('no data');

            }

        },
        error: function (error) {

        }
    });
}

//Agregar producto
function agregarProd(id) {
    var producto = id;
    var action = 'infoProducto';

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: { action: action, producto: producto },

        success: function (response) {
            if (response != 'error') {
                //console.log(response);
                var info = JSON.parse(response);

                $('.bodyModal').html('<form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); sendDataProduct();">' +
                    '<h1><i class="fas fa-cubes" style="font-size: 45pt;"></i> <br> Agregar Producto</h1>' +
                    '<h2 class="nameProducto">' + info.descripcion + '</h2> <br>' +
                    '<input type="number" name="cantidad" id="txtCantidad" placeholder="Cantidad del producto" min="1" required><br>' +
                    '<input type="text" name="precio" id="txtPrecio" placeholder="Precio del producto" required>' +
                    '<input type="hidden" name="producto_id" id="producto_id" value="' + info.codproducto + '" required>' +
                    '<input type="hidden" name="action" value="addProduct" required>' +
                    '<div class="alert alertAddProduct"></div>' +
                    '<a href="#" class="btn_ok closeModal" onclick="coloseModal(); "><i class="fas fa-ban"></i> Cerrar</a>' +
                    '<button type="submit" class="btn_new"><i class="fas fa-plus"></i> Agregar</button>' +
                    '</form>');
            }
        },

        error: function (error) {
            console.log(error);
        }

    });

    $('.modal').fadeIn();

}

//Modal editar producto
function editarProducto(id) {
    var producto = id;
    var action = 'editarProducto';

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: { action: action, producto: producto },

        success: function (response) {
            //console.log(response);            
            if (response != 'error') {
                var info = JSON.parse(response);

                foto = '';
                classRemove = '';
                if (info.producto.foto != 'img_producto.png') {
                    classRemove = '';
                    foto = '<img id="img" src="img/uploads/' + info.producto.foto + '" alt="Producto">';
                } else {
                    classRemove = 'notBlock';
                    foto = '<img id="img" src="img/' + info.producto.foto + '" alt="Producto">';
                }
                $('.bodyModal').html('<form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); actualizarProducto();">' +
                    '<input type="hidden" name="action" value="actualizarProducto">' +
                    '<h1><i class="fa fa-cube" style="font-size: 25pt;"></i> <br> Actualizar producto</h1>' +
                    '<input type="hidden" name="idProducto" value="' + info.producto.codproducto + '">' +
                    '<select name="nombreProveedorProd" id="nombreProveedorProd" required style="margin-bottom:5px;"><option value="' + info.producto.codproveedor + '">' + info.producto.proveedor + '</option>' + info.proveedor + '</select>' +
                    '<div style="display:flex; gap:5px; margin-bottom:5px;">' +
                    '<select name="categoriaProducto" id="categoriaProducto" style="margin-bottom:0; flex:1;"><option value="">Sin Categoría</option>' + info.categoria + '</select>' +
                    '<button type="button" class="btn_new" style="background:#2c3e50; border-color:#2c3e50; padding:0 12px; margin:0;" onclick="nuevaCategoriaRapida(\'categoriaProducto\')" title="Nueva Categoría"><i class="fas fa-plus"></i></button>' +
                    '</div>' +
                    '<div style="display:flex; gap:5px; margin-bottom:5px;">' +
                    '<select name="marcaProducto" id="marcaProducto" style="margin-bottom:0; flex:1;"><option value="">Sin Marca</option>' + info.marca + '</select>' +
                    '<button type="button" class="btn_new" style="background:#2c3e50; border-color:#2c3e50; padding:0 12px; margin:0;" onclick="nuevaMarcaRapida(\'marcaProducto\')" title="Nueva Marca"><i class="fas fa-plus"></i></button>' +
                    '</div>' +
                    '<div style="display:flex; gap:5px; margin-bottom:5px;">' +
                    '<select name="tamanoProducto" id="tamanoProducto" style="margin-bottom:0; flex:1;"><option value="">Sin Tamaño</option>' + info.tamano + '</select>' +
                    '<button type="button" class="btn_new" style="background:#2c3e50; border-color:#2c3e50; padding:0 12px; margin:0;" onclick="nuevoTamanoRapido(\'tamanoProducto\')" title="Nuevo Tamaño"><i class="fas fa-plus"></i></button>' +
                    '</div>' +
                    '<div style="display:flex; gap:5px; align-items:center; margin-bottom:5px;">' +
                    '<input type="text" name="codigoProducto" id="codigoProducto" value="' + info.producto.codigo + '" placeholder="Código de barras" required style="margin-bottom:0; flex:1;">' +
                    '<button type="button" class="btn_new" style="background:#2c3e50; border-color:#2c3e50; padding:8px 12px; font-size:12px; line-height:1; height:auto; display:inline-flex; align-items:center; gap:4px; margin:0;" onclick="generarCodigoForm(\'codigoProducto\', \'barcodePreviewEdit\')"><i class="fas fa-barcode"></i> Generar</button>' +
                    '<button type="button" class="btn_new" style="background:#27ae60; border-color:#27ae60; padding:8px 12px; font-size:12px; line-height:1; height:auto; display:inline-flex; align-items:center; gap:4px; margin:0;" title="Imprimir Código de Barras" onclick="modalImprimirBarcode(\'' + info.producto.codproducto + '\', \'' + addslashesJS(info.producto.codigo) + '\', \'' + addslashesJS(info.producto.descripcion) + '\', \'' + addslashesJS(info.producto.precio) + '\', \'' + addslashesJS(info.producto.codigo_interno || '') + '\')"><i class="fas fa-print"></i></button>' +
                    '</div>' +
                    '<input type="text" name="codigoInternoProducto" id="codigoInternoProducto" value="' + (info.producto.codigo_interno || '') + '" placeholder="Código interno (opcional, ej: REF-001)" style="margin-bottom:5px;">' +
                    '<div style="text-align:center; margin-bottom:5px;">' +
                    '<svg id="barcodePreviewEdit" style="max-width:100%; height:45px;"></svg>' +
                    '</div>' +
                    '<input type="text" name="nombreProducto" id="nombreProducto" value="' + info.producto.descripcion + '" placeholder="Nombre del producto" required style="margin-bottom:5px;">' +
                    '<div style="display:flex; gap:5px; margin-bottom:5px;">' +
                    '<input step="any" type="number" name="costoProducto" id="costoProducto" value="' + info.producto.costo + '" placeholder="Costo" min="0" required style="margin-bottom:0; flex:1;">' +
                    '<input step="any" type="number" name="prcioProducto" id="prcioProducto" value="' + info.producto.precio + '" placeholder="Precio" min="0" required style="margin-bottom:0; flex:1;">' +
                    '</div>' +
                    '<div style="display:flex; gap:5px; margin-bottom:5px;">' +
                    '<input step="any" type="number" name="precioMayoristaProducto" id="precioMayoristaProducto" value="' + (info.producto.precio_mayorista || '') + '" placeholder="P. Mayorista" min="0" style="margin-bottom:0; flex:1;">' +
                    '<input type="number" name="existenciaProducto" id="existenciaProducto" value="' + (info.producto.existencia || '0') + '" placeholder="Stock" min="0" style="margin-bottom:0; flex:1;">' +
                    '</div>' +
                    '<div class="photo" style="margin-top:0;"><label for="foto"></label><div class="prevPhoto">' +
                    '<span class="' + classRemove + '"></span>' +
                    '<label for="foto"></label>' + foto + '</div><div class="upimg"></div><br>' +
                    '<input type="file" name="foto" id="foto">' +
                    '<div class="alert alertAddProduct"></div>' +
                    '<a href="#" class="btn_ok closeModal" onclick="coloseModal(); "><i class="fas fa-ban"></i> Cerrar</a>' +
                    '<button type="submit" class="btn_new"><i class="fas fa-plus"></i> Guardar</button>' +
                    '</form>');

                updateBarcodePreview(info.producto.codigo, 'barcodePreviewEdit');
                $('#codigoProducto').on('input keyup change', function(){
                    updateBarcodePreview($(this).val(), 'barcodePreviewEdit');
                });

                $('#categoriaProducto').val(info.producto.categoria_id || "");
                $('#marcaProducto').val(info.producto.marca_id || "");
                $('#tamanoProducto').val(info.producto.tamano_id || "");
            }
        },

        error: function (error) {
            console.log(error);
        }

    });

    $('.modal').fadeIn();

}

//Actualizar producto
function actualizarProducto() {

    $('.alertAddProduct').html('');
    var parametros = new FormData($('#form_add_product')[0]);
    var busquedaProd = $('#busquedaProducto').val();

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        data: parametros,
        contentType: false,
        processData: false,

        success: function (response) {
            //console.log(response);
            var info = JSON.parse(response);
            if (info.cod == '00') {
                $('.alertAddProduct').html('<p style="color:green;">' + info.msg + '</p>');
                $('#form_add_product')[0].reset();
                listaProductos(busquedaProd, 1, 10);
                setTimeout(function () {
                    coloseModal();
                }, 1500);
            } else {
                $('.alertAddProduct').html('<p style="color:red;">' + info.msg + '</p>');
            }
        },

        error: function (error) {
            console.log(error);
        }

    });
}

//Modal eliminar producto//
function infoEliminarProducto(id) {
    var producto = id;
    var action = 'editarProducto';

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: { action: action, producto: producto },

        success: function (response) {
            //console.log(response);            
            if (response != 'error') {
                var info = JSON.parse(response);

                $('.bodyModal').html('<form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); eliminarProducto();">' +
                    '<input type="hidden" name="action" value="eliminarProducto">' +
                    '<h1><i class="fas fa-user" style="font-size: 45pt;"></i> <br> Desactivar producto</h1>' +
                    '<input type="hidden" name="producto_id2" id="producto_id2" value="' + info.producto.codproducto + '">' +
                    '<p>¿Está seguro de eliminar el siguiente registro?</p>' +
                    '<h2 class="nameProducto">' + info.producto.proveedor + '</h2> <br>' +
                    '<h2 class="nameProducto">' + info.producto.descripcion + '</h2> <br>' +
                    '<div class="alert alertAddProduct"></div>' +
                    '<a href="#" class="btn_ok closeModal" onclick="coloseModal(); "><i class="fas fa-ban"></i> Cerrar</a>' +
                    '<button type="submit" class="btn_new"><i class="fas fa-plus"></i> Desactivar</button>' +
                    '</form>');
            }
        },

        error: function (error) {
            console.log(error);
        }

    });

    $('.modal').fadeIn();

}

//Eliminar producto
function eliminarProducto() {

    var producto = $('#producto_id2').val();
    var descripcion = $('#busquedaProducto').val();
    $('.alertAddProduct').html('');

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: $('#form_add_product').serialize(),

        success: function (response) {
            if (response == 'error') {
                $('alertAddProduct').html('<p style="color: red;">Error al eliminar el producto.</p>');
            } else {
                $('.row' + producto).remove();
                $('#form_add_product .btn_new').remove();
                $('.alertAddProduct').html('<p>Producto eliminado correctamente.</p>');
                listaProductos(descripcion, 1, 10);
            }

        },

        error: function (error) {
            console.log(error);
        }

    });

}


//Registrar producto
function nuevoProducto() {

    var parametros = new FormData($('#form_add_product')[0]);
    $('.alertAddProduct').html('');

    $.ajax({
        url: 'ajax.php',
        type: "POST",
        data: parametros,
        contentType: false,
        processData: false,

        success: function (response) {
            //console.log(response);          
            var info = JSON.parse(response);
            if (info.cod == '00') {
                $('.alertAddProduct').html('<p style="color:green;">' + info.msg + '</p>');
                $('#form_add_product')[0].reset();
                listaProductos('', 1, 10);
                setTimeout(function () {
                    if (info.codigo && info.descripcion) {
                        modalImprimirBarcode(info.codproducto, info.codigo, info.descripcion, info.precio, info.codigo_interno);
                    } else {
                        coloseModal();
                    }
                }, 600);
            } else {
                $('.alertAddProduct').html('<p style="color:red;">' + info.msg + '</p>');
            }

        },

        error: function (error) {
            console.log(error);
        }

    });

}

//Lista ventas
function listaVentas(busqueda, pagina, cantidad, fecha_de, fecha_a, filtro_producto, filtro_pago) {
    var pagina = pagina;
    $.ajax({
        url: 'action/data_ventas.php',
        type: "POST",
        data: {
            pagina: pagina,
            busqueda: busqueda,
            cantidad: cantidad,
            fecha_de: fecha_de,
            fecha_a: fecha_a,
            filtro_producto: filtro_producto,
            filtro_pago: filtro_pago
        },

        success: function (response) {
            //console.log(response);
            if (response != 'error') {
                var info = JSON.parse(response);
                $('#listaVentas').html(info.detalle);
                $('#paginadorVentas').html(info.totales);

            } else {
                $('#listaVentas').html('<table>' +
                    '<tr>' +
                    '<th>No.</th>' +
                    '<th>Fecha</th>' +
                    '<th>Cliente</th>' +
                    '<th>Vendedor</th>' +
                    '<th>Estado</th>' +
                    '<th class="textright">Total Factura</th>' +
                    '<th class="textright">Acciones</th>' +
                    '</tr>' +
                    '<tbody>' +
                    '<tr><td colspan="7">No se encontraron concidencias :(</td></tr>' +
                    '</tbody>');
                $('#paginadorVentas').html('');
                //console.log('no data');

            }

        },
        error: function (error) {

        }
    });
}

//Modal Form Anular Factura
function infoAnularFactura(nofactura) {
    /*Act on the event*/
    //e.preventDefault();
    var nofactura = nofactura;
    var action = 'infoFactura';

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: { action: action, nofactura: nofactura },

        success: function (response) {
            if (response != 'error') {
                var info = JSON.parse(response);


                $('.bodyModal').html('<form action="" method="post" name="form_anular_factura" id="form_anular_factura" onsubmit="event.preventDefault(); anularFactura();">' +
                    '<h1><i class="fas fa-cubes" style="font-size: 45pt;"></i> <br> Anular Venta</h1><br>' +
                    '<p>¿Realmente desea anular la venta?</p>' +
                    '<p><strong>No. ' + info.noventa + '</strong></p>' +
                    '<p><strong>Monto. C$ ' + info.totalventa + '</strong></p>' +
                    '<p><strong>Fecha. ' + info.fecha + '</strong></p>' +
                    '<input type="hidden" name="action" value="anularFactura">' +
                    '<input type="hidden" name="no_factura" id="no_factura" value="' + info.noventa + '" required>' +

                    '<div class="alert alertAddProduct"></div>' +
                    '<a href="#" class="btn_ok" onclick="coloseModal();"><i class="fas fa-ban"></i> Cerrar</a>' +
                    '<button type="submit" class="btn_ok"><i class="far fa-trash-alt"></i> Anular</button>' +
                    '</form>');
            }
        },

        error: function (error) {
            console.log(error);
        }

    });

    $('.modal').fadeIn();

}

//Modal Form Anular Factura
function infoAnularFacturaCompra(nofactura) {
    /*Act on the event*/
    //e.preventDefault();
    var nofactura = nofactura;
    var action = 'infoCompra';

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: { action: action, nofactura: nofactura },

        success: function (response) {
            if (response != 'error') {
                var info = JSON.parse(response);


                $('.bodyModal').html('<form action="" method="post" name="form_anular_factura" id="form_anular_factura" onsubmit="event.preventDefault(); anularFacturaCompra();">' +
                    '<h1><i class="fas fa-cubes" style="font-size: 45pt;"></i> <br> Anular Compra</h1><br>' +
                    '<p>¿Realmente desea anular la compra?</p>' +
                    '<p><strong>No. ' + info.nocompra + '</strong></p>' +
                    '<p><strong>Monto. C$ ' + info.totalcompra + '</strong></p>' +
                    '<p><strong>Fecha. ' + info.fecha + '</strong></p>' +
                    '<input type="hidden" name="action" value="anularFactura">' +
                    '<input type="hidden" name="no_factura" id="no_factura" value="' + info.nocompra + '" required>' +

                    '<div class="alert alertAddProduct"></div>' +
                    '<a href="#" class="btn_ok" onclick="coloseModal();"><i class="fas fa-ban"></i> Cerrar</a>' +
                    '<button type="submit" class="btn_ok"><i class="far fa-trash-alt"></i> Anular</button>' +
                    '</form>');
            }
        },

        error: function (error) {
            console.log(error);
        }

    });

    $('.modal').fadeIn();

}
//Ver compra
function verFacturaCompra(codcliente, nofactura) {
    var codCliente = codcliente;
    var noFactura = nofactura;
    generarPDF_compra(codCliente, noFactura);
}


//Ver Factura
function verFactura(codcliente, nofactura) {
    var codCliente = codcliente;
    var noFactura = nofactura;
    generarPDF(codCliente, noFactura);
}

//Ver Factura
function verTicket(codcliente, nofactura) {
    var codCliente = codcliente;
    var noFactura = nofactura;
    generarPDFTicket(codCliente, noFactura);
}

//Agregar producto al detalle con enter
function agregarProductoAlDetalle() {
    if ($('#txt_cant_producto').val() > 0) {
        var codproducto = $('#txt_id_producto').val();
        var cantidad = $('#txt_cant_producto').val();
        var existencia = parseInt($('#txt_existencia').html());
        var precio = $('#txt_precio').val();
        var action = 'addProductoDetalle';
        if (cantidad > existencia) {
            alert('No hay inventario suficiente.');
            return false;
        }


        $.ajax({
            url: 'ajax.php',
            type: "POST",
            async: true,
            data: { action: action, producto: codproducto, cantidad: cantidad, precio: precio, descuento: $('#descuneto_venta').val() },
            success: function (response) {
                //console.log(response);
                if (response != 'error') {
                    var info = JSON.parse(response);
                    $('#detalle_venta').html(info.detalle);
                    $('#detalle_totales').html(info.totales);
                    if (info.total_con_desc_raw !== undefined) {
                        window.totalVentaGlobal = info.total_con_desc_raw;
                    }

                    $('#txt_id_producto').val('');
                    $('#txt_cod_producto').val('');
                    $('#txt_descripcion').html('-');
                    $('#txt_existencia').html('-');
                    $('#txt_cant_producto').val('0');
                    $('#txt_precio').val('0.00');
                    $('#txt_precio_total').html('0.00');

                    //Bloquear cantidad
                    $('#txt_cant_producto').attr('disabled', 'disabled');
                    $('#txt_precio').attr('disabled', 'disabled');
                    $('#txt_id_producto').attr('disabled', 'disabled');

                    //Ocultar boton agregar
                    $('#add_product_venta').slideUp();
                    $('#txt_cod_producto').focus();

                } else {
                    console.log('no data');

                }
                viewProcesar();

            },
            error: function (error) {

            }
        });
    }
}

function facturar() {
    var rows = $('#detalle_venta tr').length;
    if (rows > 0) {
        var action = 'procesarVenta';
        var codcliente = $('#idcliente').val();
        var tipoPago = $('#tipo_pago').val();
        var descuento = $('#descuneto_venta').val();
        var comprobante = $('#comprobante').val();

        if (descuento == '') {
            descuento = 0;
        }

        // 1. EFECTIVO (Con cálculo de vuelto)
        if (tipoPago == '1') {
            modalPagoEfectivo();
            return;
        }

        // 2. TRANSFERENCIA (2), QR (4), TARJETA (5)
        if (tipoPago == '2' || tipoPago == '4' || tipoPago == '5') {
            var nombreStorage = '';
            var actionGuardar = '';
            var funcMostrarModal = null;

            if (tipoPago == '2') {
                nombreStorage = 'transferencia_pendiente';
                actionGuardar = 'guardarTransferencia';
                funcMostrarModal = mostrarModalTransferencia;
            } else if (tipoPago == '4') {
                nombreStorage = 'pago_qr_pendiente';
                actionGuardar = 'guardarPagoQR';
                funcMostrarModal = mostrarModalPagoQR;
            } else if (tipoPago == '5') {
                nombreStorage = 'pago_tarjeta_pendiente';
                actionGuardar = 'guardarPagoTarjeta';
                funcMostrarModal = mostrarModalPagoTarjeta;
            }

            var datosPendientes = sessionStorage.getItem(nombreStorage);

            if (!datosPendientes) {
                alert('Por favor complete los datos del pago primero.');
                if (typeof funcMostrarModal === 'function') {
                    funcMostrarModal();
                }
                return;
            }

            // Procesar venta normal primero
            $.ajax({
                url: 'ajax.php',
                type: "POST",
                data: {
                    action: action,
                    codcliente: codcliente,
                    tipoPago: tipoPago,
                    descuento: descuento,
                    pago_con: 0,
                    vuelto: 0
                },
                success: function (response) {
                    console.log("Respuesta procesarVenta:", response);
                    if (response != 'error') {
                        try {
                            var info = JSON.parse(response);
                            console.log("Venta procesada con éxito, noventa:", info.noventa);
                            var noventa = info.noventa;

                            // Guardar datos adicionales del pago (referencias, etc)
                            var pagoData = JSON.parse(datosPendientes);
                            pagoData.noventa = noventa;
                            pagoData.action = actionGuardar;

                            $.ajax({
                                url: 'ajax.php',
                                type: "POST",
                                data: pagoData,
                                dataType: 'json',
                                success: function (resSave) {
                                    console.log("Respuesta guardarDetallesPago:", resSave);
                                    if (resSave.cod == '00') {
                                        sessionStorage.removeItem(nombreStorage);
                                        // Generar PDF
                                        if (comprobante == 1) {
                                            generarPDFTicket(info.codcliente, info.noventa);
                                        } else {
                                            generarPDF(info.codcliente, info.noventa);
                                        }
                                        location.reload();
                                    } else {
                                        alert('Error al guardar detalles del pago: ' + resSave.msg);
                                    }
                                }
                            });
                        } catch (e) {
                            console.error("Error al parsear JSON o ejecutar lógica:", e, response);
                            alert('Error al procesar la respuesta del servidor.');
                        }
                    } else {
                        alert('Error al procesar la venta.');
                    }
                }
            });
            return;
        }

        // 3. OTROS (Crédito, etc)
        ejecutarFacturar(action, codcliente, tipoPago, descuento, comprobante, 0, 0);
    }
}

function modalPagoEfectivo() {
    var totalText = $('#detalle_totales tr:last td:nth-child(2)').text();
    // Remover puntos de miles y comas decimales para formato Paraguay
    // Ejemplo: "208.000" -> "208000"
    var totalLimpio = totalText.replace(/\./g, '').replace(/,/g, '.');
    var totalVenta = window.totalVentaGlobal ? window.totalVentaGlobal : parseFloat(totalLimpio.replace(/[^0-9.-]+/g, ""));


    $('.bodyModal').html('<form action="" method="post" name="form_pago_efectivo" id="form_pago_efectivo" onsubmit="event.preventDefault(); procesarPagoEfectivo();">' +
        '<h1><i class="fas fa-money-bill-wave" style="font-size: 45pt;"></i> <br> Pago en Efectivo</h1>' +
        '<div style="width: 100%; text-align: center; margin-top: 10px;">' +
        '<h2 style="font-size: 18pt;">Total a Pagar: <span id="total_modal">' + totalText + '</span></h2>' +
        '<input type="hidden" id="monto_total_pago" value="' + totalVenta + '">' +
        '</div>' +
        '<div style="margin-top: 20px;">' +
        '<label style="display: block; font-size: 14pt;">Efectivo Recibido:</label>' +
        '<input type="number" step="any" name="pago_con" id="pago_con" placeholder="0" style="font-size: 20pt; text-align: center; width: 100%;" min="0" required autoFocus>' +
        '</div>' +
        '<div style="margin-top: 20px;">' +
        '<h2 style="font-size: 18pt; color: #27ae60;">Su Vuelto: <span id="vuelto_pago">0</span></h2>' +
        '<input type="hidden" id="vuelto_final" value="0">' +
        '</div>' +
        '<div class="alert alertAddProduct"></div>' +
        '<div style="margin-top: 20px;">' +
        '<a href="#" class="btn_ok" onclick="coloseModal();"><i class="fas fa-ban"></i> Cancelar</a>' +
        '<button type="submit" class="btn_new"><i class="fas fa-check"></i> Finalizar Venta</button>' +
        '</div>' +
        '</form>');

    $('.modal').fadeIn();
    $('#pago_con').focus();

    $('#pago_con').keyup(function () {
        var pago = parseFloat($(this).val());
        var total = parseFloat($('#monto_total_pago').val());
        if (pago >= total) {
            var vuelto = pago - total;
            $('#vuelto_pago').html(vuelto.toFixed(0));
            $('#vuelto_final').val(vuelto.toFixed(0));
            $('.alertAddProduct').html('');
        } else {
            $('#vuelto_pago').html('0');
            $('#vuelto_final').val(0);
        }
    });
}

function procesarPagoEfectivo() {
    var total = parseFloat($('#monto_total_pago').val());
    var pago = parseFloat($('#pago_con').val());
    var vuelto = parseFloat($('#vuelto_final').val());
    var action = 'procesarVenta';
    var codcliente = $('#idcliente').val();
    var tipoPago = $('#tipo_pago').val();
    var descuento = $('#descuneto_venta').val();
    var comprobante = $('#comprobante').val();

    if (pago < total) {
        $('.alertAddProduct').html('<p style="color:red;">El monto recibido es menor al total.</p>');
        return false;
    }

    coloseModal();
    ejecutarFacturar(action, codcliente, tipoPago, descuento, comprobante, pago, vuelto);
}

function ejecutarFacturar(action, codcliente, tipoPago, descuento, comprobante, pago_con, vuelto) {
    if (descuento == '') {
        descuento = 0;
    }

    $.ajax({
        url: 'ajax.php',
        type: "POST",
        async: true,
        data: {
            action: action,
            codcliente: codcliente,
            tipoPago: tipoPago,
            descuento: descuento,
            pago_con: pago_con,
            vuelto: vuelto
        },

        success: function (response) {
            console.log(response);
            if (response != 'error') {
                var info = JSON.parse(response);
                if (comprobante == 1) {
                    generarPDFTicket(info.codcliente, info.noventa);
                } else if (comprobante == 2) {
                    generarPDF(info.codcliente, info.noventa);
                }
                location.reload();
            } else {
                console.log('no data');
            }
        },
        error: function (error) {
            console.log(error);
        }
    });
}

function anularVent() {
    var rows = $('#detalle_venta tr').length;
    if (rows > 0) {
        var action = 'anularVenta';

        $.ajax({
            url: 'ajax.php',
            type: "POST",
            async: true,
            data: { action: action },

            success: function (response) {
                if (response != 'error') {
                    location.reload();
                }
            },
            error: function (error) {
            }
        });
    }
}

function anularCompra() {
    var rows = $('#detalle_venta_compra tr').length;
    if (rows > 0) {
        var action = 'anularCompra';

        $.ajax({
            url: 'ajax.php',
            type: "POST",
            async: true,
            data: { action: action },

            success: function (response) {
                if (response != 'error') {
                    location.reload();
                }
            },
            error: function (error) {
            }
        });
    }
}

function soloLetras(e) {
    var key = e.keyCode || e.which,
        tecla = String.fromCharCode(key).toLowerCase(),
        letras = " áéíóúabcdefghijklmnñopqrstuvwxyz",
        especiales = [8, 37, 39, 46],
        tecla_especial = false;

    for (var i in especiales) {
        if (key == especiales[i]) {
            tecla_especial = true;
            break;
        }
    }

    if (letras.indexOf(tecla) == -1 && !tecla_especial) {
        return false;
    }
}

//Modal activar producto//
function infoActivarProducto(id) {
    var producto = id;
    var action = 'editarProducto';

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: { action: action, producto: producto },

        success: function (response) {
            //console.log(response);            
            if (response != 'error') {
                var info = JSON.parse(response);

                $('.bodyModal').html('<form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); activarProducto();">' +
                    '<input type="hidden" name="action" value="activarProducto">' +
                    '<h1><i class="fas fa-user" style="font-size: 45pt;"></i> <br> Activar este producto?</h1>' +
                    '<input type="hidden" name="producto_id_2" id="producto_id_2" value="' + info.producto.codproducto + '">' +
                    '<p>¿Está seguro de activar el siguiente producto?</p>' +
                    '<h2 class="nameProducto">' + info.producto.proveedor + '</h2> <br>' +
                    '<h2 class="nameProducto">' + info.producto.descripcion + '</h2> <br>' +
                    '<div class="alert alertAddProduct"></div>' +
                    '<a href="#" class="btn_ok closeModal" onclick="coloseModal(); "><i class="fas fa-ban"></i> Cerrar</a>' +
                    '<button type="submit" class="btn_new"><i class="fas fa-plus"></i> Activar</button>' +
                    '</form>');
            }
        },

        error: function (error) {
            console.log(error);
        }

    });

    $('.modal').fadeIn();

}

//Activar producto
function activarProducto() {

    var producto = $('#producto_id_2').val();
    var descripcion = $('#busquedaProducto').val();
    $('.alertAddProduct').html('');

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: $('#form_add_product').serialize(),

        success: function (response) {
            if (response == 'error') {
                $('alertAddProduct').html('<p style="color: red;">Error al activar el producto.</p>');
            } else {
                $('.row' + producto).remove();
                $('#form_add_product .btn_new').remove();
                $('.alertAddProduct').html('<p>Producto activado correctamente.</p>');
                listaProductos(descripcion, 1, 10);
                setTimeout(function () {
                    coloseModal();
                }, 1500);
            }

        },

        error: function (error) {
            console.log(error);
        }

    });

}
//Lista de creditos
function listaCreditos(busqueda, pagina) {

    var pagina = pagina;
    $.ajax({
        url: 'action/data_credito.php',
        type: "POST",
        data: { pagina: pagina, busqueda: busqueda },

        success: function (response) {
            //console.log(response);
            if (response != 'error') {
                var info = JSON.parse(response);
                $('#cuentas_por_cobrar').html(info.detalle);
                $('#paginador_por_cobrar').html(info.totales);

            } else {
                $('#cuentas_por_cobrar').html('<table>' +
                    '<tr>' +
                    '<th>No.</th>' +
                    '<th>Fecha</th>' +
                    '<th>Cliente</th>' +
                    '<th>Vendedor</th>' +
                    '<th>Total factura</th>' +
                    '<th>Estado</th>' +
                    '<th>Acción</th>' +
                    '</tr>' +
                    '<tbody>' +
                    '<tr><td colspan="7">No se encontraron concidencias :(</td></tr>' +
                    '</tbody>');
                $('#paginador_por_cobrar').html('');
                //console.log('no data');

            }

        },
        error: function (error) {

        }
    });
}

//Lista Movimientos
function listaMovimientos(busqueda, pagina, cantidad) {
    var pagina = pagina;
    var busqueda = $('#busquedaMov').val();
    $.ajax({
        url: 'action/data_movimientos.php',
        type: "POST",
        data: { pagina: pagina, busqueda: busqueda, cantidad: cantidad },

        success: function (response) {
            console.log(response);
            if (response != 'error') {
                var info = JSON.parse(response);
                $('#listaMovimientos').html(info.detalle);
                $('#paginadorMovimientos').html(info.totales);

            } else {
                $('#listaMovimientos').html('<table>' +
                    '<tr>' +
                    '<th>No.</th>' +
                    '<th>Fecha</th>' +
                    '<th>Cliente</th>' +
                    '<th>Vendedor</th>' +
                    '<th>Estado</th>' +
                    '<th class="textright">Total Factura</th>' +
                    '<th class="textright">Acciones</th>' +
                    '</tr>' +
                    '<tbody>' +
                    '<tr><td colspan="7">No se encontraron concidencias :(</td></tr>' +
                    '</tbody>');
                $('#paginadorMovimientos').html('');
                console.log('no data');

            }

        },
        error: function (error) {

        }
    });
}

//Modal  agregar nueva factura//
function add_fact_cliente(id) {
    var cliente = id;
    var action = 'info_cuenta_cobrar';

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: { action: action, cliente: cliente },

        success: function (response) {
            //console.log(response);            
            if (response != 'error') {
                var info = JSON.parse(response);
                var saldo = info.totalventa - info.abono;


                $('.bodyModal').html('<form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); nueva_factura_cobrar();">' +
                    '<input type="hidden" name="action" value="add_factura_cliente">' +
                    '<h1><i class="fa fa-file-alt fa-w-12" style="font-size: 45pt;"></i> <br> <br>Agregar Factura</h1>' +
                    '<input type="hidden" name="id_client_fact" id="id_client_fact" value="' + info.codcliente + '">' +
                    '<p>¿Está seguro de agregar la siguiente factura?</p>' +
                    '<h2>Nombre: ' + info.cliente + '</h2>' +
                    '<h2>Saldo: C$ ' + saldo + '</h2>' +
                    '<label style="text-align: left;">Cantidad:</label>' +
                    '<input type="number" step="any" name="nuevaFactura" id="nuevaFactura" value="" placeholder="C$ 0.00" min="1" required><br>' +
                    '<div class="alert alertAddProduct"></div>' +
                    '<a href="#" class="btn_ok closeModal" onclick="coloseModal(); "><i class="fas fa-ban"></i> Cerrar</a>' +
                    '<button type="submit" class="btn_new"><i class="fas fa-plus"></i> Guardar</button>' +
                    '</form>');
            }
        },

        error: function (error) {
            console.log(error);
        }

    });

    $('.modal').fadeIn();

}

//Registrar nueva factura
function nueva_factura_cobrar() {

    $('.alertAddProduct').html('');
    var id = $('#id_client_fact').val();
    var cantidad = $('#nuevaFactura').val();

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: $('#form_add_product').serialize(),

        success: function (response) {
            //console.log(response);           
            var info = JSON.parse(response);
            if (info.cod == '00') {
                $('.alertAddProduct').html('<p style="color:green;">' + info.msg + '</p>');
                $('#form_add_product')[0].reset();
                listaCreditos('', 1, 10);
                setTimeout(function () {
                    coloseModal();
                }, 1500);
            } else {
                $('.alertAddProduct').html('<p style="color:red;">' + info.msg + '</p>');
            }


        },

        error: function (error) {
            console.log(error);
        }

    });

}

//Modal agregar nuevo pago//
function add_abono_cliente(cliente, nofactura) {
    var nofactura = nofactura;
    var cliente = cliente;
    var action = 'info_cuenta_cobrar';

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: { action: action, cliente: cliente, nofactura: nofactura },

        success: function (response) {
            //console.log(response);            
            if (response != 'error') {
                var info = JSON.parse(response);
                var saldo = info.totalventa - info.abono;

                $('.bodyModal').html('<form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); nuevo_abono_clinete();">' +
                    '<input type="hidden" name="action" value="add_abono_cliente">' +
                    '<h1><i class="fas fa-money-bill-alt" style="font-size: 45pt;"></i> <br> <br>Realizar Pago</h1>' +
                    '<input type="hidden" name="id_venta_abono" id="id_venta_abono" value="' + info.noventa + '">' +
                    '<input type="hidden" name="id_client_abono" id="id_client_abono" value="' + info.codcliente + '">' +
                    '<input type="hidden" name="id_client_saldo" id="id_client_saldo" value="' + saldo + '">' +
                    '<p>¿Está seguro de realizar el siguiente pago?</p>' +
                    '<h2>Nombre: ' + info.cliente + '</h2>' +
                    '<h2>Saldo: C$ ' + saldo + '</h2>' +
                    '<label class="textcenter">Cantidad:</label>' +
                    '<input class="textcenter" type="number" step="any" name="nuevoAbono" id="nuevaAbono" value="" placeholder="C$ 0.00" min="1" required><br>' +
                    '<div class="alert alertAddProduct"></div>' +
                    '<a href="#" class="btn_ok closeModal" onclick="coloseModal(); "><i class="fas fa-ban"></i> Cerrar</a>' +
                    '<button type="submit" class="btn_new"><i class="fas fa-plus"></i> Guardar</button>' +
                    '</form>');
            }
        },

        error: function (error) {
            console.log(error);
        }

    });

    $('.modal').fadeIn();

}

//Registrar nuevo abono
function nuevo_abono_clinete() {

    $('.alertAddProduct').html('');
    var id = $('#id_client_abono').val();
    var nofactura = $('#id_venta_abono').val();
    var cantidad = $('#nuevaAbono').val();
    var saldo = parseInt($('#id_client_saldo').val());

    if (saldo < cantidad) {
        $('.alertAddProduct').html('<p style="color:red;">La cantidad debe ser menor o igual que saldo.</p>');
        $('.alertAddProduct').slideDown();
        return false;
    }

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: $('#form_add_product').serialize(),

        success: function (response) {
            //console.log(response);           
            var info = JSON.parse(response);
            if (info.cod == '00') {
                generarReciboPDF(id, info.msg);
                $('#form_add_product')[0].reset();
                listaCreditos('', 1, 10);
                setTimeout(function () {
                    coloseModal();
                }, 1500);
            } else {
                $('.alertAddProduct').html('<p style="color:red;">' + info.msg + '</p>');
            }

            // $('.modal').fadeOut();
            //listaMovimientos();
        },

        error: function (error) {
            console.log(error);
        }

    });

}

//Agregar new factura
function new_fact_cobrar() {

    $('.alertAddProduct').html('');
    var cliente = $('#new_cliente_fact').val();
    var noDoc = $('#new_num_fact').val();
    var cantidad = $('#new_fact').val();

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: $('#agregar_new_factura').serialize(),

        success: function (response) {
            //console.log(response);           
            var info = JSON.parse(response);
            if (info.cod == '00') {
                $('.alertAddProduct').html('<p style="color:green;">' + info.msg + '</p>');
                $('#agregar_new_factura')[0].reset();
                listaCreditos('', 1, 11);
                setTimeout(function () {
                    coloseModal();
                }, 1500);
            } else {
                $('.alertAddProduct').html('<p style="color:red;">' + info.msg + '</p>');
            }


        },

        error: function (error) {
            console.log(error);
        }

    });

}

//Lista Recibos
function listaRecibos(pagina, busqueda) {
    var pagina = pagina;
    var busqueda = $('#busquedaRecibo').val();
    $.ajax({
        url: 'action/data_recibo.php',
        type: "POST",
        data: { pagina: pagina, busqueda: busqueda },

        success: function (response) {
            //console.log(response);
            if (response != 'error') {
                var info = JSON.parse(response);
                $('#listaRecibos').html(info.detalle);
                $('#paginadorRecibos').html(info.totales);

            } else {
                $('#listaRecibos').html('<table>' +
                    '<tr>' +
                    '<th>Id.</th>' +
                    '<th>No. Venta</th>' +
                    '<th>Fecha</th>' +
                    '<th>Saldo anterio</th>' +
                    '<th>Abono</th>' +
                    '<th>Saldo actual</th>' +
                    '<th class="textcenter">Acciones</th>' +
                    '</tr>' +
                    '<tbody>' +
                    '<tr><td colspan="7">No se encontraron concidencias :(</td></tr>' +
                    '</tbody>');
                $('#paginadorRecibos').html('');
                console.log('no data');

            }

        },
        error: function (error) {

        }
    });
}

function generarReciboPDF(cliente, factura) {
    var ancho = 1000;
    var alto = 800;
    //Calcularposicion x,y para centrar la ventana
    var x = parseInt((window.screen.width / 2) - (ancho / 2));
    var y = parseInt((window.screen.height / 2) - (alto / 2));

    $url = 'factura/generarRecibo.php?cl=' + cliente + '&f=' + factura;
    window.open($url, "Factura", "left=" + x + ",top=" + y + ",height=" + alto + ",width=" + ancho + ",scrollbar=si,location=no,resizable=si,menubar=no");
}

function verRecibo(codcliente, nofactura) {
    var codCliente = codcliente;
    var noFactura = nofactura;
    generarReciboPDF(codCliente, noFactura);
}

//Agregar new factura proveedor
function new_fact_pagar() {

    $('.alertAddProduct').html('');
    var cliente = $('#new_proveedor_fact').val();
    var noDoc = $('#new_prov_fact').val();
    var cantidad = $('#cantidad_fact_prov').val();

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: $('#agregar_prov_factura').serialize(),

        success: function (response) {
            //console.log(response);           
            var info = JSON.parse(response);
            if (info.cod == '00') {
                $('.alertAddProduct').html('<p style="color:green;">' + info.msg + '</p>');
                $('#agregar_prov_factura')[0].reset();
                lista_cuentas_por_pagar('', 1, 10);
                setTimeout(function () {
                    coloseModal();
                }, 1500);
            } else {
                $('.alertAddProduct').html('<p style="color:red;">' + info.msg + '</p>');
            }


        },

        error: function (error) {
            console.log(error);
        }

    });

}

//Lista de cuentas por pagar
function lista_cuentas_por_pagar(busqueda, pagina) {

    var pagina = pagina;
    $.ajax({
        url: 'action/data_por_pagar.php',
        type: "POST",
        data: { pagina: pagina, busqueda: busqueda },

        success: function (response) {
            //console.log(response);
            if (response != 'error') {
                var info = JSON.parse(response);
                $('#cuentas_por_pagar').html(info.detalle);
                $('#paginador_por_pagar').html(info.totales);

            } else {
                $('#cuentas_por_pagar').html('<table>' +
                    '<tr>' +
                    '<th>Id</th>' +
                    '<th>Fecha</th>' +
                    '<th>Proveedor</th>' +
                    '<th>Total factura</th>' +
                    '<th>Estado</th>' +
                    '<th>Acción</th>' +
                    '</tr>' +
                    '<tbody>' +
                    '<tr><td colspan="7">No se encontraron concidencias :(</td></tr>' +
                    '</tbody>');
                $('#paginador_por_pagar').html('');
                //console.log('no data');

            }

        },
        error: function (error) {

        }
    });
}

//Lista Movimientos proveedores
function lista_Mov_proveedor(busqueda, pagina, cantidad) {
    var pagina = pagina;
    var busqueda = $('#busquedaMov_proveedor').val();
    $.ajax({
        url: 'action/data_mov_proveedor.php',
        type: "POST",
        data: { pagina: pagina, busqueda: busqueda, cantidad: cantidad },

        success: function (response) {
            //console.log(response);
            if (response != 'error') {
                var info = JSON.parse(response);
                $('#listaMov_proveedor').html(info.detalle);
                $('#paginadorMov_proveedor').html(info.totales);

            } else {
                $('#listaMov_proveedor').html('<table>' +
                    '<tr>' +
                    '<th>No.</th>' +
                    '<th>Fecha</th>' +
                    '<th>Usuario</th>' +
                    '<th>Proveedor</th>' +
                    '<th>Estado</th>' +
                    '<th class="textright">Total Factura</th>' +
                    '<th class="textright">Abono</th>' +
                    '<th class="textright">Saldo total</th>' +
                    '<th class="textright">Acciones</th>' +
                    '</tr>' +
                    '<tbody>' +
                    '<tr><td colspan="7">No se encontraron concidencias :(</td></tr>' +
                    '</tbody>');
                $('#paginadorMov_proveedor').html('');
                console.log('no data');

            }

        },
        error: function (error) {

        }
    });
}

//Modal agregar nuevo pago proveedor
function add_abono_proveedor(proveedor, nofactura) {
    var nofactura = nofactura;
    var proveedor = proveedor;
    var action = 'info_cuenta_pagar';

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: { action: action, proveedor: proveedor, nofactura: nofactura },

        success: function (response) {
            //console.log(response);            
            if (response != 'error') {
                var info = JSON.parse(response);
                var saldo = info.totalcompra - info.abono;

                $('.bodyModal').html('<form action="" method="post" name="form_add_product" id="form_add_product" onsubmit="event.preventDefault(); nuevo_abono_proveedor();">' +
                    '<input type="hidden" name="action" value="add_abono_proveedor">' +
                    '<h1><i class="fas fa-money-bill-alt" style="font-size: 45pt;"></i> <br> <br>Realizar Pago</h1>' +
                    '<input type="hidden" name="id_compra_abono" id="id_compra_abono" value="' + info.nocompra + '">' +
                    '<input type="hidden" name="id_prov_abono" id="id_prov_abono" value="' + info.codproveedor + '">' +
                    '<input type="hidden" name="saldo_anterior" id="saldo_anterior" value="' + saldo + '">' +
                    '<p>¿Está seguro de realizar el siguiente pago?</p>' +
                    '<h2>Proveedor: ' + info.proveedor + '</h2>' +
                    '<h2>Saldo: C$ ' + saldo + '</h2>' +
                    '<label class="textcenter">Cantidad:</label>' +
                    '<input class="textcenter" type="number" step="any" name="nuevoAbono_prov" id="nuevoAbono_prov" value="" placeholder="C$ 0.00" min="1" required><br>' +
                    '<div class="alert alertAddProduct"></div>' +
                    '<a href="#" class="btn_ok closeModal" onclick="coloseModal(); "><i class="fas fa-ban"></i> Cerrar</a>' +
                    '<button type="submit" class="btn_new"><i class="fas fa-plus"></i> Guardar</button>' +
                    '</form>');
            }
        },

        error: function (error) {
            console.log(error);
        }

    });

    $('.modal').fadeIn();

}

//Registrar nuevo abono
function nuevo_abono_proveedor() {

    $('.alertAddProduct').html('');
    var id = $('#id_prov_abono').val();
    var nofactura = $('#id_compra_abono').val();
    var cantidad = $('#nuevoAbono_prov').val();
    var saldo = parseInt($('#saldo_anterior').val());

    if (saldo < cantidad) {
        $('.alertAddProduct').html('<p style="color:red;">La cantidad debe ser menor o igual que saldo.</p>');
        $('.alertAddProduct').slideDown();
        return false;
    }

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: $('#form_add_product').serialize(),

        success: function (response) {
            //console.log(response);           
            var info = JSON.parse(response);
            if (info.cod == '00') {
                //generarReciboPDF(id,info.msg);
                $('.alertAddProduct').html('<p style="color:green;">' + info.msg + '</p>');
                $('#form_add_product')[0].reset();
                lista_cuentas_por_pagar('', 1, 10);
                setTimeout(function () {
                    coloseModal();
                }, 1500);
            } else {
                $('.alertAddProduct').html('<p style="color:red;">' + info.msg + '</p>');
            }

            //$('.modal').fadeIn();
        },

        error: function (error) {
            console.log(error);
        }

    });

}

//Lista Recibos proveedor
function listaRecibos_proveedor(pagina, busqueda) {
    var pagina = pagina;
    var busqueda = $('#busquedaRecibo_prov').val();
    $.ajax({
        url: 'action/data_recibo_proveedor.php',
        type: "POST",
        data: { pagina: pagina, busqueda: busqueda },

        success: function (response) {
            //console.log(response);
            if (response != 'error') {
                var info = JSON.parse(response);
                $('#listaRecibos_proveedor').html(info.detalle);
                $('#paginadorRecibos_proveedor').html(info.totales);

            } else {
                $('#listaRecibos_proveedor').html('<table>' +
                    '<tr>' +
                    '<th>Id.</th>' +
                    '<th>No. compra.</th>' +
                    '<th>Fecha</th>' +
                    '<th>Saldo anterio</th>' +
                    '<th>Abono</th>' +
                    '<th>Saldo actual</th>' +
                    '</tr>' +
                    '<tbody>' +
                    '<tr><td colspan="7">No se encontraron concidencias :(</td></tr>' +
                    '</tbody>');
                $('#paginadorRecibos_proveedor').html('');
                console.log('no data');

            }

        },
        error: function (error) {

        }
    });
}

//Procesar compra
function comprar() {
    var rows = $('#detalle_venta_compra tr').length;
    if (rows > 0) {
        var action = 'procesarCompra';
        var codproveedor = $('#idproveedor').val();
        var tipoPago = $('#tipo_pago').val();

        $.ajax({
            url: 'ajax.php',
            type: "POST",
            async: true,
            data: {
                action: action,
                codproveedor: codproveedor,
                tipoPago: tipoPago
            },
            success: function (response) {
                console.log('Respuesta RAW:', response); // ✅ VER RESPUESTA COMPLETA

                try {
                    if (response != 'error') {
                        var info = JSON.parse(response);

                        if (info.error) {
                            alert('Error: ' + info.error);
                        } else {
                            alert('Compra procesada correctamente');
                            location.reload();
                        }
                    } else {
                        alert('Error al procesar la compra');
                    }
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    console.error('Respuesta recibida:', response);
                    alert('Error: La respuesta del servidor no es válida. Revisa la consola.');
                }
            },
            error: function (xhr, status, error) {
                console.error('Error AJAX:', { xhr, status, error });
                console.error('Respuesta:', xhr.responseText);
                alert('Error de conexión: ' + error);
            }
        });
    }
}

function serchForDetalleCompra(id) {
    var action = 'serchForDetalleCompra';
    var user = id;

    $.ajax({
        url: 'ajax.php',
        type: "POST",
        async: true,
        data: { action: action, user: user },

        success: function (response) {
            if (response != 'error') {
                var info = JSON.parse(response);
                $('#detalle_venta_compra').html(info.detalle);
                $('#detalle_totales_compra').html(info.totales);

            } else {
                console.log('no data');

            }
            viewProcesarCompra();
        },
        error: function (error) {

        }
    });
}

//Agregar producto al detalle con enter
function agregarProductoAlDetalleCompra() {
    if ($('#txt_cant_producto_compra').val() > 0) {
        var codproducto = $('#txt_id_producto_compra').val();
        var cantidad = $('#txt_cant_producto_compra').val();
        var costo = $('#txt_precio_compra').val();
        var existencia = parseInt($('#txt_existencia_compra').html());
        var action = 'addProductoDetalleCompra';

        $.ajax({
            url: 'ajax.php',
            type: "POST",
            async: true,
            data: { action: action, producto: codproducto, cantidad: cantidad, costo: costo },
            success: function (response) {
                //console.log(response);
                if (response != 'error') {
                    var info = JSON.parse(response);
                    $('#detalle_venta_compra').html(info.detalle);
                    $('#detalle_totales_compra').html(info.totales);

                    $('#txt_id_producto_compra').val('');
                    $('#txt_cod_producto_compra').val('');
                    $('#txt_descripcion_compra').html('-');
                    $('#txt_existencia_compra').html('-');
                    $('#txt_cant_producto_compra').val('0');
                    $('#txt_precio_compra').val('0.00');
                    $('#txt_precio_total_compra').html('0.00');

                    //Bloquear cantidad
                    $('#txt_cant_producto_compra').attr('disabled', 'disabled');
                    $('#txt_precio_compra').attr('disabled', 'disabled');
                    $('#txt_id_producto_compra').attr('disabled', 'disabled');

                    //Ocultar boton agregar
                    $('#add_product_venta').slideUp();
                    $('#txt_cod_producto_compra').focus();

                } else {
                    console.log('no data');

                }
                viewProcesarCompra();

            },
            error: function (error) {

            }
        });
    }
}

//Borrar producto del detalle temporal
function del_product_detalle_compra(correlativo) {
    var action = 'delProductoDetalleCompra';
    var id_detalle = correlativo;

    $.ajax({
        url: 'ajax.php',
        type: "POST",
        async: true,
        data: { action: action, id_detalle: id_detalle },

        success: function (response) {
            //console.log(response);
            if (response != 'error') {
                var info = JSON.parse(response);

                $('#detalle_venta_compra').html(info.detalle);
                $('#detalle_totales_compra').html(info.totales);

                $('#txt_cod_producto_compra').val('');
                $('#txt_descripcion_compra').html('-');
                $('#txt_existencia_compra').html('-');
                $('#txt_cant_producto_compra').val('0');
                $('#txt_precio_compra').html('0.00');
                $('#txt_precio_total_compra').html('0.00');
                $('#txt_cod_producto_compra').focus();

                //Bloquear cantidad
                $('#txt_cant_producto_compra').attr('disabled', 'disabled');

                //Ocultar boton agregar
                $('#add_product_venta').slideUp();

            } else {
                $('#detalle_venta_compra').html('');
                $('#detalle_totales_compra').html('');
            }
            viewProcesarCompra();

        },
        error: function (error) {

        }
    });

}

function edit_product_detalle_compra(correlativo, cantidad_actual) {
    var nueva_cantidad = prompt("Ingrese la nueva cantidad a cargar:", cantidad_actual);
    if (nueva_cantidad != null && nueva_cantidad.trim() !== "") {
        nueva_cantidad = parseInt(nueva_cantidad);
        if (isNaN(nueva_cantidad) || nueva_cantidad <= 0) {
            alert("Por favor ingrese una cantidad válida mayor a 0.");
            return;
        }
        var action = 'updateProductoDetalleCompra';
        
        $.ajax({
            url: 'ajax.php',
            type: "POST",
            async: true,
            data: { action: action, id_detalle: correlativo, cantidad: nueva_cantidad },
            success: function (response) {
                if (response != 'error') {
                    var info = JSON.parse(response);
                    $('#detalle_venta_compra').html(info.detalle);
                    $('#detalle_totales_compra').html(info.totales);
                    // viewProcesarCompra is needed if it exists, viewProcesar is used in del_product_detalle_compra so we call it:
                    viewProcesarCompra();
                } else {
                    console.log('no data');
                }
            },
            error: function (error) {
                console.log(error);
            }
        });
    }
}

function agregarProductoCompra(codigo) {
    var action = 'addProductoDetalleCompra';

    var fechaVenc = $('#txt_fecha_vencimiento').val();

    // Si hay fecha, validar que no sea pasada
    if (fechaVenc) {
        var hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        var fechaIngresada = new Date(fechaVenc);

        if (fechaIngresada < hoy) {
            alert('La fecha de vencimiento no puede ser anterior a hoy');
            return false;
        }
    }

    $.ajax({
        url: 'ajax.php',
        type: "POST",
        async: true,
        data: $('#form_del_product').serialize(), // Esto incluye la fecha
        success: function (response) {
            console.log('Respuesta servidor:', response); // Para debug

            if (response != 'error') {
                var info = JSON.parse(response);
                $('#detalle_venta_compra').html(info.detalle);
                $('#detalle_totales_compra').html(info.totales);

                $('#busquedaProdCompra').val('');
                $('#busquedaProdCompra').focus();
                $('.modal').fadeOut();
            } else {
                alert('Error al agregar el producto');
                console.log('Error en respuesta');
            }
            viewProcesarCompra();
        },
        error: function (xhr, status, error) {
            console.error('Error AJAX:', { xhr, status, error });
            alert('Error al procesar: ' + error);
        }
    });
}

function serchForDetalleProdCompra(busquedaProd, pagina) {
    var pagina = pagina;
    $.ajax({
        url: 'action/data_producto_compra.php',
        type: "POST",
        data: { pagina: pagina, busquedaProd: busquedaProd },

        success: function (response) {
            //console.log(response);
            if (response != 'error') {
                var info = JSON.parse(response);
                $('#dataProdCompra').html(info.detalle);
                $('#paginadorProdCompra').html(info.totales);

            } else {
                $('#dataProdCompra').html('<table>' +
                    '<tr>' +
                    '<th>Código</th>' +
                    '<th>Descripción</th>' +
                    '<th>Existencia</th>' +
                    '<th>Costo</th>' +
                    '<th>Foto</th>' +
                    '<th>Cantidad</th>' +
                    '<th>Acción</th>' +
                    '</tr>' +
                    '<tbody>' +
                    '<tr><td colspan="7">No se encontraron concidencias :(</td></tr>' +
                    '</tbody>');
                $('#paginadorProdCompra').html('');
                //console.log('no data');

            }

        },
        error: function (error) {

        }

    });
}

//Lista ventas
function listaCompras(busqueda, pagina, cantidad) {
    var pagina = pagina;
    $.ajax({
        url: 'action/data_compras.php',
        type: "POST",
        data: { pagina: pagina, busqueda: busqueda, cantidad: cantidad },

        success: function (response) {
            //console.log(response);
            if (response != 'error') {
                var info = JSON.parse(response);
                $('#listaCompras').html(info.detalle);
                $('#paginadorCompras').html(info.totales);

            } else {
                $('#listaCompras').html('<table>' +
                    '<tr>' +
                    '<th>No.</th>' +
                    '<th>Fecha</th>' +
                    '<th>Proveedor</th>' +
                    '<th>Usuario</th>' +
                    '<th>Estado</th>' +
                    '<th class="textright">Total Compra</th>' +
                    '<th class="textright">Acciones</th>' +
                    '</tr>' +
                    '<tbody>' +
                    '<tr><td colspan="7">No se encontraron concidencias :(</td></tr>' +
                    '</tbody>');
                $('#paginadorCompras').html('');
                //console.log('no data');

            }

        },
        error: function (error) {

        }
    });
}

//Registrar cliente
function devolucion() {

    $('.alertAddProduct').html('');

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: $('#form_add_product').serialize(),

        success: function (response) {
            //console.log(response);           
            var info = JSON.parse(response);
            if (info.cod == '00') {
                $('.alertAddProduct').html('<p style="color:green;">' + info.msg + '</p>');
                $('#form_add_product')[0].reset();
                listaVentas('', 1, 10);
            } else {
                $('.alertAddProduct').html('<p style="color:red;">' + info.msg + '</p>');
            }

        },

        error: function (error) {
            console.log(error);
        }

    });
}

//Modal Form Anular Factura
function infoAnularRecibo(nofactura) {
    /*Act on the event*/
    //e.preventDefault();
    var nofactura = nofactura;
    var action = 'infoFactura';

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: { action: action, nofactura: nofactura },

        success: function (response) {
            if (response != 'error') {
                var info = JSON.parse(response);


                $('.bodyModal').html('<form action="" method="post" name="form_anular_factura" id="form_anular_factura" onsubmit="event.preventDefault(); anularRecibo();">' +
                    '<h1><i class="fas fa-cubes" style="font-size: 45pt;"></i> <br> Anular Recibo</h1><br>' +
                    '<p>¿Realmente desea anular este recibo?</p>' +
                    '<p><strong>No. ' + info.noventa + '</strong></p>' +
                    '<p><strong>Monto. C$ ' + info.abono + '</strong></p>' +
                    '<p><strong>Fecha. ' + info.fecha + '</strong></p>' +
                    '<input type="hidden" name="action" value="anularRecibo">' +
                    '<input type="hidden" name="no_recibo" id="no_recibo" value="' + info.noventa + '" required>' +

                    '<div class="alert alertAddProduct"></div>' +
                    '<a href="#" class="btn_ok" onclick="coloseModal();"><i class="fas fa-ban"></i> Cerrar</a>' +
                    '<button type="submit" class="btn_ok"><i class="far fa-trash-alt"></i> Anular</button>' +
                    '</form>');
            }
        },

        error: function (error) {
            console.log(error);
        }

    });

    $('.modal').fadeIn();

}

function anularRecibo() {
    var noventa = $('#no_recibo').val();
    var action = 'anularRecibo';

    $.ajax({
        url: 'ajax.php',
        type: "POST",
        async: true,
        data: { action: action, noventa: noventa },

        success: function (response) {
            //console.log(response);
            if (response != 'error') {
                //location.reload();
            }
        },
        error: function (error) {
        }
    });

}

// Auxiliares para Egresos
function listaGastosFiltrados() {
    var busqueda = $('#busquedaEgresos').val();
    var f_de = $('#fecha_de').val();
    var f_a = $('#fecha_a').val();
    var cant = $('#cantidad_mostrar_egresos').val() || 10;
    listaGastos(busqueda, 1, cant, f_de, f_a);
}

function generarReporteEgreso() {
    var f_de = $('#fecha_de').val();
    var f_a = $('#fecha_a').val();
    var busqueda = $('#busquedaEgresos').val();
    var url = 'factura/generaReporteEgreso.php?f_de=' + f_de + '&f_a=' + f_a + '&b=' + busqueda;
    window.open(url, '_blank');
}

//Lista de gastos
function listaGastos(busqueda, pagina, cantidad, f_de = '', f_a = '') {
    $.ajax({
        url: 'action/data_gastos.php',
        type: "POST",
        data: {
            pagina: pagina,
            busqueda: busqueda,
            cantidad: cantidad,
            f_de: f_de,
            f_a: f_a
        },
        success: function (response) {
            if (response != 'error') {
                var info = JSON.parse(response);
                $('#listaEgresos').html(info.detalle);
                $('#paginadoEgresos').html(info.totales);
            } else {
                $('#listaEgresos').html('<table>' +
                    '<tr>' +
                    '<th>Fecha</th>' +
                    '<th>Tipo</th>' +
                    '<th>Descripción</th>' +
                    '<th>Local</th>' +
                    '<th>Cantidad</th>' +
                    '<th>Usuario</th>' +
                    '<th>Acción</th>' +
                    '</tr>' +
                    '<tbody>' +
                    '<tr><td colspan="7" style="text-align:center;">No se encontraron registros en este periodo :(</td></tr>' +
                    '</tbody>');
                $('#paginadoEgresos').html('');
            }
        }
    });
}

//Lista de cajas
function listaCajas(busqueda, pagina, cantidad) {

    var pagina = pagina;
    $.ajax({
        url: 'action/data_caja.php',
        type: "POST",
        data: { pagina: pagina, busqueda: busqueda, cantidad: cantidad },

        success: function (response) {
            //console.log(response);
            if (response != 'error') {
                var info = JSON.parse(response);
                $('#listaCaja').html(info.detalle);
                $('#paginadoCaja').html(info.totales);

            } else {
                $('#listaCaja').html('<table>' +
                    '<tr>' +
                    '<th>Fecha</th>' +
                    '<th>Inicio</th>' +
                    '<th>Ventas</th>' +
                    '<th>Abonos</th>' +
                    '<th>Créditos</th>' +
                    '<th>Egresos</th>' +
                    '<th>Total efectivo</th>' +
                    '</tr>' +
                    '<tbody>' +
                    '<tr><td colspan="7">No se encontraron concidencias :(</td></tr>' +
                    '</tbody>');
                $('#paginadoCaja').html('');
                //console.log('no data');

            }

        },
        error: function (error) {

        }
    });
}

//Registrar Egreso
function nuevoEgreso() {

    var pago = $('#cantEgreso').val();
    var caja = parseInt($('#total_caja').val());

    $('.alertAddProduct').html('');
    if (pago > caja) {
        $('.alertAddProduct').html('<p style="color:red;"> No hay dinero suficiente para realizar el pago.</p>');
        return false;
    }

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: $('#form_add_product').serialize(),

        success: function (response) {
            //console.log(response);           
            var info = JSON.parse(response);
            if (info.cod == '00') {
                $('.alertAddProduct').html('<p style="color:green;">' + info.msg + '</p>');
                $('#form_add_product')[0].reset();
                listaGastos('', 1, 10);
                setTimeout(function () {
                    coloseModal();
                }, 1500);
            } else {
                $('.alertAddProduct').html('<p style="color:red;">' + info.msg + '</p>');
            }


        },

        error: function (error) {
            console.log(error);
        }

    });

}

//Modal Form Anular egreso
function infoAnularEgreso(id) {
    /*Act on the event*/
    //e.preventDefault();
    var nofactura = id;
    var action = 'infoEgreso';
    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: { action: action, nofactura: nofactura },

        success: function (response) {
            //console.log(response);            
            if (response != 'error') {
                var info = JSON.parse(response);


                $('.bodyModal').html('<form action="" method="post" name="form_anular_factura" id="form_anular_factura" onsubmit="event.preventDefault(); anularEgreso();">' +
                    '<h1><i class="fas fa-cubes" style="font-size: 45pt;"></i> <br> Anular Egreso</h1><br>' +
                    '<p>¿Realmente desea anular este egreso?</p>' +
                    '<p><strong>No. ' + info.descripcion + '</strong></p>' +
                    '<p><strong>Monto. C$ ' + info.cantidad + '</strong></p>' +
                    '<input type="hidden" name="action" value="anularEgreso">' +
                    '<input type="hidden" name="id_gasto" id="id_gasto" value="' + info.id + '" required>' +
                    '<div class="alert alertAddProduct"></div>' +
                    '<a href="#" class="btn_ok" onclick="coloseModal();"><i class="fas fa-ban"></i> Cerrar</a>' +
                    '<button type="submit" class="btn_ok"><i class="far fa-trash-alt"></i> Eliminar</button>' +
                    '</form>');
            }
        },

        error: function (error) {
            console.log(error);
        }

    });

    $('.modal').fadeIn();

}

//Anular factura
function anularEgreso() {
    var noFactura = $('#id_gasto').val();
    var action = 'anularEgreso';

    $.ajax({
        url: 'ajax.php',
        type: "POST",
        async: true,
        data: { action: action, noFactura: noFactura },

        success: function (response) {
            //console.log(response);
            if (response == 'error') {
                $('.alertAddProduct').html('<p style="color:red;">Error al anular elgreso.</p>');
            } else {
                $('#form_anular_factura .btn_ok').remove();
                $('.alertAddProduct').html('<p>Egreso anulado correctamente.</p>');
                listaGastos('', 1, 10);
                setTimeout(function () {
                    coloseModal();
                }, 1500);
            }
        },
        error: function (error) {

        }
    });
}


//abrir caja
function nuevaCaja() {

    $('.alertAddProduct').html('');

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: $('#form_add_product').serialize(),

        success: function (response) {
            //console.log(response);           
            var info = JSON.parse(response);
            if (info.cod == '00') {
                $('.alertAddProduct').html('<p style="color:green;">' + info.msg + '</p>');
                $('#form_add_product')[0].reset();
                setTimeout(function () {
                    coloseModal();
                }, 1500);
            } else {
                $('#form_anular_factura .btn_ok').remove();
                $('.alertAddProduct').html('<p style="color:red;">' + info.msg + '</p>');
            }
            location.reload();

        },

        error: function (error) {
            console.log(error);
        }

    });
}

//Cerrar caja
function cerrarCaja() {

    $('.alertAddProduct').html('');

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: $('#form_cierre_caja').serialize(),

        success: function (response) {
            //console.log(response);           
            var info = JSON.parse(response);
            if (info.cod == '00') {
                $('.alertAddProduct').html('<p style="color:green;">' + info.msg + '</p>');
                location.reload();
            } else {
                $('.alertAddProduct').html('<p style="color:red;">' + info.msg + '</p>');
            }


        },

        error: function (error) {
            console.log(error);
        }

    });

}

function generarEstado() {
    var fecha_de = $('#desde').val();
    var fecha_a = $('#hasta').val();

    generarReportePDF_estadoR(fecha_de, fecha_a);
    location.reload();
}

function reporteProducto() {
    var codigo = $('#codigoRepProd').val();
    var fecha_de = $('#inicioReporteProd').val();
    var fecha_a = $('#finReporteProd').val();

    generarReporteProducto(fecha_de, fecha_a, codigo);
    //location.reload();
}

//Modal agregar producto venta
function infoProductAgregar(codigo) {
    /*Act on the event*/
    //e.preventDefault();
    var producto = codigo;
    var action = 'infoProducto';
    var descuento = $('#descuneto_venta').val();

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: { action: action, producto: producto },

        success: function (response) {
            if (response != 'error') {
                var info = JSON.parse(response);

                var precioNormal = parseFloat(info.precio) || 0;
                var precioMayorista = parseFloat(info.precio_mayorista) || 0;
                var mayoristaDisponible = precioMayorista > 0;
                var precioInicial = precioNormal;
                var mayoristaTexto = mayoristaDisponible ? precioMayorista : 'No definido';

                $('.bodyModal').html('<form action="" method="post" name="form_del_product" id="form_del_product" onsubmit="event.preventDefault(); agregarProducto();">' +
                    '<h2 class="nameProducto">' + info.codigo + '</h2>' +
                    '<p>' + info.descripcion + '</p>' +
                    '<p><strong>Precio normal:</strong> ' + precioNormal + '</p>' +
                    '<p><strong>Precio mayorista:</strong> ' + mayoristaTexto + '</p>' +
                    '<label style="display:flex; align-items:center; gap:6px; justify-content:center; margin:10px 0;"><input type="checkbox" id="usar_mayorista_venta" name="usar_mayorista_venta" ' + (mayoristaDisponible ? '' : 'disabled') + '> Usar precio mayorista</label>' +
                    '<h1> Ingrese la cantidad</h1>' +
                    '<input class="textcenter" type="number" name="txt_cant_producto_venta" id="txt_cant_producto_venta" value="1" min="1" required>' +
                    '<input type="hidden" name="txt_existencia_venta" id="txt_existencia_venta" value="' + info.existencia + '" required>' +
                    '<input type="hidden" step="any" name="txt_precio_venta" id="txt_precio_venta" value="' + precioInicial + '" required>' +
                    '<input type="hidden" name="txt_codigo_venta" id="txt_codigo_venta" value="' + info.codigo + '" required>' +
                    '<input type="hidden" name="txt_cod_producto_venta" id="txt_cod_producto_venta" value="' + info.codproducto + '" required>' +
                    '<input type="hidden" name="descuento" id="descuento" value="' + descuento + '" required>' +
                    '<input type="hidden" name="action" value="addProductoDetalle2" required>' +
                    '<div class="alert alertAddProduct"></div>' +
                    '<a href="#" class="btn_ok" onclick="coloseModal();"><i class="fas fa-ban"></i> Cerrar</a>' +
                    '<button type="submit" class="btn_cancel"><i class="fas fa-plus"></i> Agregar</button>' +
                    '</form>');
                $('.modal').fadeIn();
                function actualizarPrecioMayorista() {
                    var cantidadVenta = parseInt($('#txt_cant_producto_venta').val()) || 1;
                    var usarMayorista = mayoristaDisponible && cantidadVenta >= 3;
                    $('#usar_mayorista_venta').prop('checked', usarMayorista);
                    $('#txt_precio_venta').val(usarMayorista ? precioMayorista : precioNormal);
                }
                $('#txt_cant_producto_venta').on('input change', actualizarPrecioMayorista);
                $('#usar_mayorista_venta').on('change', function () {
                    var precioSeleccionado = $(this).is(':checked') && precioMayorista > 0 ? precioMayorista : precioNormal;
                    $('#txt_precio_venta').val(precioSeleccionado);
                });
            }
            $('#txt_cant_producto_venta').focus();
            $('#busquedaProd').val('');
        },

        error: function (error) {
            console.log(error);
        }

    });
}

//Modal agregar producto venta
function infoProductAgregarEnter(codigo) {
    /*Act on the event*/
    //e.preventDefault();
    var producto = codigo;
    var action = 'infoProductoEnter';
    var descuento = $('#descuneto_venta').val();

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: { action: action, producto: producto },

        success: function (response) {
            if (response != 'error') {
                var info = JSON.parse(response);

                var precioNormal = parseFloat(info.precio) || 0;
                var precioMayorista = parseFloat(info.precio_mayorista) || 0;
                var mayoristaDisponible = precioMayorista > 0;
                var precioInicial = precioNormal;
                var mayoristaTexto = mayoristaDisponible ? precioMayorista : 'No definido';

                $('.bodyModal').html('<form action="" method="post" name="form_del_product" id="form_del_product" onsubmit="event.preventDefault(); agregarProducto();">' +
                    '<h2 class="nameProducto">' + info.codigo + '</h2>' +
                    '<p>' + info.descripcion + '</p>' +
                    '<p><strong>Precio normal:</strong> ' + precioNormal + '</p>' +
                    '<p><strong>Precio mayorista:</strong> ' + mayoristaTexto + '</p>' +
                    '<label style="display:flex; align-items:center; gap:6px; justify-content:center; margin:10px 0;"><input type="checkbox" id="usar_mayorista_venta" name="usar_mayorista_venta" ' + (mayoristaDisponible ? '' : 'disabled') + '> Usar precio mayorista</label>' +
                    '<h1> Ingrese la cantidad</h1>' +
                    '<input class="textcenter" type="number" name="txt_cant_producto_venta" id="txt_cant_producto_venta" value="1" min="1" required>' +
                    '<input type="hidden" name="txt_existencia_venta" id="txt_existencia_venta" value="' + info.existencia + '" required>' +
                    '<input type="hidden" step="any" name="txt_precio_venta" id="txt_precio_venta" value="' + precioInicial + '" required>' +
                    '<input type="hidden" name="txt_codigo_venta" id="txt_codigo_venta" value="' + info.codigo + '" required>' +
                    '<input type="hidden" name="txt_cod_producto_venta" id="txt_cod_producto_venta" value="' + info.codproducto + '" required>' +
                    '<input type="hidden" name="descuento" id="descuento" value="' + descuento + '" required>' +
                    '<input type="hidden" name="action" value="addProductoDetalle2" required>' +
                    '<div class="alert alertAddProduct"></div>' +
                    '<a href="#" class="btn_ok" onclick="coloseModal();"><i class="fas fa-ban"></i> Cerrar</a>' +
                    '<button type="submit" class="btn_cancel"><i class="fas fa-plus"></i> Agregar</button>' +
                    '</form>');
                $('.modal').fadeIn();
                function actualizarPrecioMayorista() {
                    var cantidadVenta = parseInt($('#txt_cant_producto_venta').val()) || 1;
                    var usarMayorista = mayoristaDisponible && cantidadVenta >= 3;
                    $('#usar_mayorista_venta').prop('checked', usarMayorista);
                    $('#txt_precio_venta').val(usarMayorista ? precioMayorista : precioNormal);
                }
                $('#txt_cant_producto_venta').on('input change', actualizarPrecioMayorista);
                $('#usar_mayorista_venta').on('change', function () {
                    var precioSeleccionado = $(this).is(':checked') && precioMayorista > 0 ? precioMayorista : precioNormal;
                    $('#txt_precio_venta').val(precioSeleccionado);
                });
            }
            $('#txt_cant_producto_venta').focus();
            $('#busquedaProd').val('');
        },

        error: function (error) {
            console.log(error);
        }

    });
}


//Modal agregar producto compra
function infoProductAgregarCompra(codigo) {
    var producto = codigo;
    var action = 'infoProducto';

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: { action: action, producto: producto },

        success: function (response) {
            if (response != 'error') {
                var info = JSON.parse(response);

                // Calcular fecha mínima (hoy)
                var hoy = new Date().toISOString().split('T')[0];

                $('.bodyModal').html('<form action="" method="post" name="form_del_product" id="form_del_product" onsubmit="event.preventDefault(); agregarProductoCompra();">' +
                    '<h2 class="nameProducto">' + info.codigo + '</h2>' +
                    '<h1>' + info.descripcion + '</h1>' +

                    '<label class="textcenter">Cantidad</label>' +
                    '<input class="textcenter" type="number" name="txt_cant_producto_compra" id="txt_cant_producto_compra" value="1" min="1" required>' +

                    '<label class="textcenter">Costo</label>' +
                    '<input class="textcenter" type="number" step="any" name="txt_precio_compra" id="txt_precio_compra" value="' + info.costo + '" min="0" required>' +

                    '<label class="textcenter">Fecha de Vencimiento (Opcional)</label>' +
                    '<input class="textcenter" type="date" name="txt_fecha_vencimiento" id="txt_fecha_vencimiento" min="' + hoy + '">' +

                    '<input type="hidden" name="txt_cod_producto_compra" id="txt_cod_producto_compra" value="' + info.codproducto + '" required>' +
                    '<input type="hidden" name="action" value="addProductoDetalleCompra" required>' +

                    '<div class="alert alertAddProduct"></div>' +
                    '<a href="#" class="btn_ok" onclick="coloseModal();"><i class="fas fa-ban"></i> Cerrar</a>' +
                    '<button type="submit" class="btn_cancel"><i class="fas fa-plus"></i> Agregar</button>' +
                    '</form>');
                $('.modal').fadeIn();
            }

            $('#txt_cant_producto_compra').focus();
            $('#busquedaProdCompra').val('');
        },

        error: function (error) {
            console.log(error);
        }
    });
}

//Modal agregar producto compra enter
function infoProductAgregarCompraEnter(codigo) {
    var producto = codigo;
    var action = 'infoProductoEnter';

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: { action: action, producto: producto },

        success: function (response) {
            if (response != 'error') {
                var info = JSON.parse(response);

                var hoy = new Date().toISOString().split('T')[0];

                $('.bodyModal').html('<form action="" method="post" name="form_del_product" id="form_del_product" onsubmit="event.preventDefault(); agregarProductoCompra();">' +
                    '<h2 class="nameProducto">' + info.codigo + '</h2>' +
                    '<h1>' + info.descripcion + '</h1>' +

                    '<label class="textcenter">Cantidad</label>' +
                    '<input class="textcenter" type="number" name="txt_cant_producto_compra" id="txt_cant_producto_compra" value="1" min="1" required>' +

                    '<label class="textcenter">Costo</label>' +
                    '<input class="textcenter" type="number" step="any" name="txt_precio_compra" id="txt_precio_compra" value="' + info.costo + '" min="0" required>' +

                    '<label class="textcenter">Fecha de Vencimiento (Opcional)</label>' +
                    '<input class="textcenter" type="date" name="txt_fecha_vencimiento" id="txt_fecha_vencimiento" min="' + hoy + '">' +

                    '<input type="hidden" name="txt_cod_producto_compra" id="txt_cod_producto_compra" value="' + info.codproducto + '" required>' +
                    '<input type="hidden" name="action" value="addProductoDetalleCompra" required>' +

                    '<div class="alert alertAddProduct"></div>' +
                    '<a href="#" class="btn_ok" onclick="coloseModal();"><i class="fas fa-ban"></i> Cerrar</a>' +
                    '<button type="submit" class="btn_cancel"><i class="fas fa-plus"></i> Agregar</button>' +
                    '</form>');
                $('.modal').fadeIn();
            }

            $('#txt_cant_producto_compra').focus();
            $('#busquedaProdCompra').val('');
        },

        error: function (error) {
            console.log(error);
        }
    });
}
// ===== JAVASCRIPT PARA DEVOLUCIONES (agregar a functions.js) =====

// Variable para almacenar la venta seleccionada
let ventaSeleccionada = null;

// Función para el botón de devolución
document.addEventListener('DOMContentLoaded', function () {
    // Agregar event listener al botón de devolución
    const btnDevolucion = document.getElementById('devolucion');
    if (btnDevolucion) {
        btnDevolucion.addEventListener('click', function (e) {
            e.preventDefault();
            procesarDevolucion();
        });
    }

    // Event listener para el formulario de devolución
    const formDevolucion = document.getElementById('formDevolucion');
    if (formDevolucion) {
        formDevolucion.addEventListener('submit', function (e) {
            e.preventDefault();
            enviarDevolucion();
        });
    }
});

// Función para seleccionar venta (agregar checkbox a cada fila de la tabla)
function seleccionarVenta(ventaId) {
    // Desmarcar otras ventas
    const checkboxes = document.querySelectorAll('input[name="venta_seleccionada"]');
    checkboxes.forEach(cb => {
        if (cb.value != ventaId) cb.checked = false;
    });

    ventaSeleccionada = ventaId;
}

// Función principal para procesar devolución
function procesarDevolucion() {
    // Verificar si hay una venta seleccionada
    const ventaCheckbox = document.querySelector('input[name="venta_seleccionada"]:checked');

    if (!ventaCheckbox) {
        alert('Por favor selecciona una venta para procesar la devolución');
        return;
    }

    ventaSeleccionada = ventaCheckbox.value;

    // Cargar datos de la venta
    cargarDatosVenta(ventaSeleccionada);
}

// Función para cargar datos de la venta
function cargarDatosVenta(ventaId) {
    $.ajax({
        url: 'ajax/obtener_venta.php',
        type: 'GET',
        data: { venta_id: ventaId },
        dataType: 'json',
        beforeSend: function () {
            // Mostrar loading
            document.getElementById('infoVenta').innerHTML = '<p>Cargando...</p>';
            document.getElementById('modalDevolucion').style.display = 'block';
        },
        success: function (response) {
            if (response.status === 'success') {
                mostrarDatosVenta(response.venta, response.productos);
            } else {
                alert('Error: ' + response.message);
                cerrarModalDevolucion();
            }
        },
        error: function (xhr, status, error) {
            console.error('Error AJAX:', { xhr, status, error });
            alert('Error al cargar los datos de la venta');
            cerrarModalDevolucion();
        }
    });
}

// Función para mostrar los datos de la venta en el modal
function mostrarDatosVenta(venta, productos) {
    // Llenar información de la venta
    document.getElementById('ventaId').value = venta.idventa;

    const infoVenta = `
        <div class="venta-info">
            <h3>Información de la Venta</h3>
            <p><strong>ID Venta:</strong> ${venta.idventa}</p>
            <p><strong>Fecha:</strong> ${venta.fecha}</p>
            <p><strong>Cliente:</strong> ${venta.cliente || 'Cliente general'}</p>
            <p><strong>Vendedor:</strong> ${venta.vendedor}</p>
            <p><strong>Total Venta:</strong> $${parseFloat(venta.totalfactura).toFixed(2)}</p>
        </div>
    `;

    document.getElementById('infoVenta').innerHTML = infoVenta;

    // Llenar productos
    let productosHTML = '';
    productos.forEach(function (producto, index) {
        productosHTML += `
            <div class="producto-devolucion">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong>${producto.descripcion}</strong><br>
                        <small>Precio unitario: $${parseFloat(producto.precio).toFixed(2)}</small><br>
                        <small>Cantidad vendida: ${producto.cantidad}</small>
                    </div>
                    <div style="text-align: right;">
                        <label>
                            <input type="checkbox" name="producto_${producto.idproducto}" 
                                   onchange="toggleProductoDevolucion(${producto.idproducto}, ${producto.cantidad}, ${producto.precio})">
                            Devolver
                        </label><br>
                        <input type="number" id="cantidad_${producto.idproducto}" 
                               min="1" max="${producto.cantidad}" value="${producto.cantidad}"
                               style="width: 80px; margin-top: 5px;" disabled
                               onchange="calcularTotalDevolucion()">
                    </div>
                </div>
            </div>
        `;
    });

    document.getElementById('productosDevolucion').innerHTML = productosHTML;
    calcularTotalDevolucion();
}

// Función para habilitar/deshabilitar cantidad de producto
function toggleProductoDevolucion(productoId, maxCantidad, precio) {
    const checkbox = document.querySelector(`input[name="producto_${productoId}"]`);
    const cantidadInput = document.getElementById(`cantidad_${productoId}`);

    if (checkbox.checked) {
        cantidadInput.disabled = false;
        cantidadInput.setAttribute('data-precio', precio);
    } else {
        cantidadInput.disabled = true;
        cantidadInput.removeAttribute('data-precio');
    }

    calcularTotalDevolucion();
}

// Función para calcular total de devolución
function calcularTotalDevolucion() {
    let total = 0;

    const checkboxes = document.querySelectorAll('#productosDevolucion input[type="checkbox"]:checked');
    checkboxes.forEach(function (checkbox) {
        const productoId = checkbox.name.split('_')[1];
        const cantidadInput = document.getElementById(`cantidad_${productoId}`);
        const precio = parseFloat(cantidadInput.getAttribute('data-precio') || 0);
        const cantidad = parseInt(cantidadInput.value || 0);

        total += precio * cantidad;
    });

    document.getElementById('totalDevolucion').textContent = total.toFixed(2);
}

// Función para enviar la devolución
function enviarDevolucion() {
    const motivo = document.getElementById('motivoDevolucion').value;
    const ventaId = document.getElementById('ventaId').value;

    if (!motivo) {
        alert('Por favor selecciona un motivo para la devolución');
        return;
    }

    // Recopilar productos seleccionados
    const productosDevueltos = [];
    const checkboxes = document.querySelectorAll('#productosDevolucion input[type="checkbox"]:checked');

    if (checkboxes.length === 0) {
        alert('Por favor selecciona al menos un producto para devolver');
        return;
    }

    checkboxes.forEach(function (checkbox) {
        const productoId = checkbox.name.split('_')[1];
        const cantidadInput = document.getElementById(`cantidad_${productoId}`);
        const precio = parseFloat(cantidadInput.getAttribute('data-precio'));
        const cantidad = parseInt(cantidadInput.value);

        productosDevueltos.push({
            id: productoId,
            cantidad: cantidad,
            precio: precio
        });
    });

    // Confirmar devolución
    const totalDevolucion = document.getElementById('totalDevolucion').textContent;
    if (!confirm(`¿Estás seguro de procesar esta devolución por $${totalDevolucion}?`)) {
        return;
    }

    // Enviar devolución
    $.ajax({
        url: 'ajax/procesar_devolucion.php',
        type: 'POST',
        data: {
            venta_id: ventaId,
            motivo: motivo,
            productos_devueltos: productosDevueltos
        },
        dataType: 'json',
        beforeSend: function () {
            document.querySelector('#formDevolucion button[type="submit"]').disabled = true;
            document.querySelector('#formDevolucion button[type="submit"]').textContent = 'Procesando...';
        },
        success: function (response) {
            if (response.status === 'success') {
                alert('Devolución procesada correctamente. Total devuelto: $' + response.total_devuelto);
                cerrarModalDevolucion();
                // Recargar la lista de ventas
                if (typeof buscar_datos_ventas === 'function') {
                    buscar_datos_ventas();
                } else {
                    location.reload();
                }
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function (xhr, status, error) {
            console.error('Error AJAX:', { xhr, status, error });
            console.error('Response text:', xhr.responseText);
            alert('Error al procesar la devolución. Revisa la consola para más detalles.');
        },
        complete: function () {
            document.querySelector('#formDevolucion button[type="submit"]').disabled = false;
            document.querySelector('#formDevolucion button[type="submit"]').textContent = 'Procesar Devolución';
        }
    });
}

// Función para cerrar modal
function cerrarModalDevolucion() {
    document.getElementById('modalDevolucion').style.display = 'none';
    document.getElementById('formDevolucion').reset();
    document.getElementById('infoVenta').innerHTML = '';
    document.getElementById('productosDevolucion').innerHTML = '';
    document.getElementById('totalDevolucion').textContent = '0.00';
    ventaSeleccionada = null;
}
// ============================================
// GESTIÓN DE TRANSFERENCIAS
// ============================================

// Variable global para almacenar bancos
var bancosList = [];

// Cargar bancos al iniciar
function cargarBancos() {
    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        data: { action: 'obtenerBancos' },
        dataType: 'json',
        success: function (response) {
            bancosList = response;
        },
        error: function (error) {
            console.log('Error al cargar bancos:', error);
        }
    });
}

// Mostrar modal de transferencia al seleccionar tipo de pago
$(document).off('change', '#tipo_pago').on('change', '#tipo_pago', function () {
    var tipoPago = $(this).val();

    if (tipoPago == '2') { // Transferencia
        mostrarModalTransferencia();
    } else if (tipoPago == '4') { // QR
        mostrarModalPagoQR();
    } else if (tipoPago == '5') { // Tarjeta
        mostrarModalPagoTarjeta();
    }
});


// Verificación al cargar la página
$(document).ready(function () {
    // Limpiar datos pendientes al cargar
    if (sessionStorage.getItem('pago_qr_pendiente')) {
        console.log('Datos de pago QR pendientes encontrados');
    }
    if (sessionStorage.getItem('pago_tarjeta_pendiente')) {
        console.log('Datos de pago tarjeta pendientes encontrados');
    }

    console.log('Sistema de pagos QR y Tarjeta inicializado');
});
// Mostrar modal pago QR
function mostrarModalPagoQR() {
    var fechaHoy = new Date().toISOString().split('T')[0];

    $('.bodyModal').html(
        '<form action="" method="post" id="form_pago_qr" onsubmit="event.preventDefault(); guardarPagoQR();">' +
        '<h1><i class="fas fa-qrcode" style="font-size: 45pt;"></i><br>Pago con QR</h1>' +
        '<input type="hidden" name="action" value="guardarPagoQR">' +

        // Removed separate select for tipo_qr, defaulting to General in backend

        '<label style="text-align:left; display:block; margin-top:15px;">Número de Referencia (Opcional):</label>' +
        '<input type="text" name="numero_referencia" id="numero_referencia_qr" placeholder="Ej: QR-123456">' +

        '<label style="text-align:left; display:block; margin-top:15px;">Fecha de Pago:</label>' +
        '<input type="date" name="fecha_pago" id="fecha_pago_qr" value="' + fechaHoy + '" required>' +

        '<label style="text-align:left; display:block; margin-top:15px;">Monto (Opcional):</label>' +
        '<input type="number" step="0.01" name="monto" id="monto_qr" placeholder="0.00" min="0">' +

        '<div class="alert alertAddProduct"></div>' +

        '<div style="margin-top:20px; text-align:center;">' +
        '<a href="#" class="btn_cancel" onclick="coloseModal(); $(\'#tipo_pago\').val(\'1\');"><i class="fas fa-ban"></i> Cancelar</a> ' +
        '<button type="submit" class="btn_new"><i class="fas fa-check"></i> Guardar y Continuar</button>' +
        '</div>' +

        '</form>'
    );

    $('.modal').fadeIn();
}

// Función para guardar pago QR
function guardarPagoQR() {
    $('.alertAddProduct').html('');

    var numero_referencia = $('#numero_referencia_qr').val();
    var fecha_pago = $('#fecha_pago_qr').val();
    var monto = $('#monto_qr').val();

    if (!fecha_pago) {
        $('.alertAddProduct').html('<p style="color:red;">La fecha de pago es obligatoria.</p>');
        return false;
    }

    // Guardar datos en sessionStorage temporalmente
    sessionStorage.setItem('pago_qr_pendiente', JSON.stringify({
        numero_referencia: numero_referencia,
        fecha_pago: fecha_pago,
        monto: monto || '0.00' // Default to 0.00 if not provided
    }));

    $('.alertAddProduct').html('<p style="color:green;">Datos guardados. Proceda a facturar.</p>');

    setTimeout(function () {
        coloseModal();
    }, 1000);
}

// ============================================
// GESTIÓN DE PAGOS CON TARJETA
// ============================================

// Mostrar modal pago Tarjeta
function mostrarModalPagoTarjeta() {
    var fechaHoy = new Date().toISOString().split('T')[0];

    $('.bodyModal').html(
        '<form action="" method="post" id="form_pago_tarjeta" onsubmit="event.preventDefault(); guardarPagoTarjeta();">' +
        '<h1><i class="fas fa-credit-card" style="font-size: 45pt;"></i><br>Pago con Tarjeta</h1>' +
        '<input type="hidden" name="action" value="guardarPagoTarjeta">' +

        // Removed inputs for type, bank, etc.

        '<label style="text-align:left; display:block; margin-top:15px;">Número de Referencia (Opcional):</label>' +
        '<input type="text" name="numero_referencia" id="numero_referencia_tarjeta" placeholder="Ej: AUTH-123456 o Lote">' +

        '<label style="text-align:left; display:block; margin-top:15px;">Fecha de Pago:</label>' +
        '<input type="date" name="fecha_pago" id="fecha_pago_tarjeta" value="' + fechaHoy + '" required>' +

        '<label style="text-align:left; display:block; margin-top:15px;">Monto (Opcional):</label>' +
        '<input type="number" step="0.01" name="monto" id="monto_tarjeta" placeholder="0.00" min="0">' +

        '<div class="alert alertAddProduct"></div>' +

        '<div style="margin-top:20px; text-align:center;">' +
        '<a href="#" class="btn_cancel" onclick="coloseModal(); $(\'#tipo_pago\').val(\'1\');"><i class="fas fa-ban"></i> Cancelar</a> ' +
        '<button type="submit" class="btn_new"><i class="fas fa-check"></i> Guardar y Continuar</button>' +
        '</div>' +

        '</form>'
    );

    $('.modal').fadeIn();
}

// Función para guardar pago con tarjeta
function guardarPagoTarjeta() {
    $('.alertAddProduct').html('');

    var numero_referencia = $('#numero_referencia_tarjeta').val();
    var fecha_pago = $('#fecha_pago_tarjeta').val();
    var monto = $('#monto_tarjeta').val();

    if (!fecha_pago) {
        $('.alertAddProduct').html('<p style="color:red;">La fecha de pago es obligatoria.</p>');
        return false;
    }

    // Guardar datos en sessionStorage temporalmente
    sessionStorage.setItem('pago_tarjeta_pendiente', JSON.stringify({
        numero_referencia: numero_referencia,
        fecha_pago: fecha_pago,
        monto: monto || '0.00' // Default to 0.00 if not provided
    }));

    $('.alertAddProduct').html('<p style="color:green;">Datos guardados. Proceda a facturar.</p>');

    setTimeout(function () {
        coloseModal();
    }, 1000);
}


// Mostrar modal transferencia
function mostrarModalTransferencia() {
    var fechaHoy = new Date().toISOString().split('T')[0];

    $('.bodyModal').html(
        '<form action="" method="post" id="form_transferencia" onsubmit="event.preventDefault(); guardarTransferencia();">' +
        '<h1><i class="fas fa-exchange-alt" style="font-size: 45pt;"></i><br>Datos de Transferencia</h1>' +
        '<input type="hidden" name="action" value="guardarTransferencia">' +

        '<label style="text-align:left; display:block; margin-top:15px;">Número de Referencia (Opcional):</label>' +
        '<input type="text" name="numero_referencia" id="numero_referencia" placeholder="Ej: TRF-123456">' +

        '<label style="text-align:left; display:block; margin-top:15px;">Fecha de Transferencia:</label>' +
        '<input type="date" name="fecha_transferencia" id="fecha_transferencia" value="' + fechaHoy + '" required>' +

        '<label style="text-align:left; display:block; margin-top:15px;">Monto (Opcional):</label>' +
        '<input type="number" step="0.01" name="monto" id="monto" placeholder="0.00" min="0">' +

        '<div class="alert alertAddProduct"></div>' +

        '<div style="margin-top:20px; text-align:center;">' +
        '<a href="#" class="btn_cancel" onclick="coloseModal(); $(\'#tipo_pago\').val(\'1\');"><i class="fas fa-ban"></i> Cancelar</a> ' +
        '<button type="submit" class="btn_new"><i class="fas fa-check"></i> Guardar y Continuar</button>' +
        '</div>' +

        '</form>'
    );

    $('.modal').fadeIn();
}

// Guardar transferencia
function guardarTransferencia() {
    $('.alertAddProduct').html('');

    var numero_referencia = $('#numero_referencia').val();
    var fecha_transferencia = $('#fecha_transferencia').val();
    var monto = $('#monto').val();

    if (!fecha_transferencia) {
        $('.alertAddProduct').html('<p style="color:red;">La fecha es obligatoria.</p>');
        return false;
    }

    // Guardar en sessionStorage para procesar al confirmar venta
    sessionStorage.setItem('transferencia_pendiente', JSON.stringify({
        numero_referencia: numero_referencia,
        fecha_transferencia: fecha_transferencia,
        monto: monto || '0.00' // Default if empty
    }));

    $('.alertAddProduct').html('<p style="color:green;">Datos guardados. Proceda a facturar.</p>');

    setTimeout(function () {
        coloseModal();
    }, 1000);
}






// Función para procesar el reporte de productos desde el modal
function reporteProducto() {
    var codigo = $('#codigoRepProd').val();
    var fecha_de = $('#inicioReporteProd').val();
    var fecha_a = $('#finReporteProd').val();

    // Si las fechas están vacías, quizás queremos todo el histórico, enviamos vacio.
    // O si preferimos poner valores por defecto, podemos hacerlo aquí.

    // Llamar a la función que abre la ventana
    generarReporteProducto(fecha_de, fecha_a, codigo);

    // Cerrar modal
    coloseModal();
}

// ============================================
// BUSCADORES EN MODALES (CLIENTES Y PROVEEDORES)
// ============================================

// --- CLIENTES ---
// --- CLIENTES ---
function abrirModalBusquedaCliente() {
    $('.bodyModal').html('<div class="modal-search-container">' +
        '<div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #05817d; margin-bottom: 20px; padding-bottom: 10px;">' +
        '<h1 style="margin: 0; color: #286581; font-size: 22px; text-transform: none;"><i class="fas fa-search" style="color: #05817d;"></i> Buscar Cliente</h1>' +
        '<a href="#" onclick="coloseModal();" style="color: #666; font-size: 18px;"><i class="fas fa-times-circle"></i></a>' +
        '</div>' +
        '<div class="search-input-wrapper">' +
        '<input type="text" name="busquedaModalCli" id="busquedaModalCli" class="modal-search-input" placeholder="Escriba nombre o cédula para buscar...">' +
        '</div>' +
        '<div id="listaClienteModal"></div>' +
        '<div class="paginador modal-search-pagination" id="paginadorClienteModal"></div>' +
        '<div style="text-align: right; margin-top: 15px;">' +
        '<a href="#" class="btn_ok" style="background: #e74c3c; padding: 8px 20px; border-radius: 4px;" onclick="coloseModal();"><i class="fas fa-ban"></i> Cancelar</a>' +
        '</div>' +
        '</div>');

    $('.modal').fadeIn();
    $('#busquedaModalCli').focus();

    listaClienteModal('', 1);

    $('#busquedaModalCli').keyup(function () {
        var busqueda = $(this).val();
        listaClienteModal(busqueda, 1);
    });
}

function listaClienteModal(busqueda, pagina) {
    $.ajax({
        url: 'action/data_cliente_modal.php',
        type: "POST",
        data: { pagina: pagina, busqueda: busqueda, cantidad: 5 },
        success: function (response) {
            if (response != 'error') {
                var info = JSON.parse(response);
                $('#listaClienteModal').html(info.detalle);
                $('#paginadorClienteModal').html(info.totales);
            } else {
                $('#listaClienteModal').html('<p class="textcenter">No se encontraron resultados.</p>');
                $('#paginadorClienteModal').html('');
            }
        },
        error: function (error) {
            console.log(error);
        }
    });
}

function seleccionarCliente(id, nombre, nit, telefono, direccion) {
    $('#idcliente').val(id);
    $('#nom_cliente').val(nombre);
    $('#nit_cliente').val(nit);
    $('#tel_cliente').val(telefono);
    $('#dir_cliente').val(direccion);

    // Desactivar campos si ya existe (lógica habitual del sistema)
    $('#nit_cliente').attr('disabled', 'disabled');
    $('#tel_cliente').attr('disabled', 'disabled');
    $('#dir_cliente').attr('disabled', 'disabled');
    $('.btn_new_cliente').slideUp();

    coloseModal();
}

// --- PROVEEDORES ---
function abrirModalBusquedaProveedor() {
    $('.bodyModal').html('<div class="modal-search-container">' +
        '<div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #05817d; margin-bottom: 20px; padding-bottom: 10px;">' +
        '<h1 style="margin: 0; color: #286581; font-size: 22px; text-transform: none;"><i class="fas fa-search" style="color: #05817d;"></i> Buscar Proveedor</h1>' +
        '<a href="#" onclick="coloseModal();" style="color: #666; font-size: 18px;"><i class="fas fa-times-circle"></i></a>' +
        '</div>' +
        '<div class="search-input-wrapper">' +
        '<input type="text" name="busquedaModalProv" id="busquedaModalProv" class="modal-search-input" placeholder="Escriba nombre o contacto para buscar...">' +
        '</div>' +
        '<div id="listaProveedorModal"></div>' +
        '<div class="paginador modal-search-pagination" id="paginadorProveedorModal"></div>' +
        '<div style="text-align: right; margin-top: 15px;">' +
        '<a href="#" class="btn_ok" style="background: #e74c3c; padding: 8px 20px; border-radius: 4px;" onclick="coloseModal();"><i class="fas fa-ban"></i> Cancelar</a>' +
        '</div>' +
        '</div>');

    $('.modal').fadeIn();
    $('#busquedaModalProv').focus();

    listaProveedorModal('', 1);

    $('#busquedaModalProv').keyup(function () {
        var busqueda = $(this).val();
        listaProveedorModal(busqueda, 1);
    });
}

function listaProveedorModal(busqueda, pagina) {
    $.ajax({
        url: 'action/data_proveedor_modal.php',
        type: "POST",
        data: { pagina: pagina, busqueda: busqueda, cantidad: 5 },
        success: function (response) {
            if (response != 'error') {
                var info = JSON.parse(response);
                $('#listaProveedorModal').html(info.detalle);
                $('#paginadorProveedorModal').html(info.totales);
            } else {
                $('#listaProveedorModal').html('<p class="textcenter">No se encontraron resultados.</p>');
                $('#paginadorProveedorModal').html('');
            }
        },
        error: function (error) {
            console.log(error);
        }
    });
}

function seleccionarProveedor(id, nombre, contacto, telefono, direccion) {
    $('#idproveedor').val(id);
    $('#nom_proveedor').val(nombre);
    $('#con_proveedor').val(contacto);
    $('#tel_proveedor').val(telefono);
    $('#dir_proveedor').val(direccion);

    // Ocultar botón de nuevo si ya existe
    $('.btn_new_proveedor').slideUp();

    coloseModal();
}

// Fin de archivo
// --- REGISTRO DE CLIENTE DESDE MODAL ---
function abrirModalNuevoCliente() {
    $('.bodyModal').html('<div class="modal-search-container">' +
        '<div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #05817d; margin-bottom: 20px; padding-bottom: 10px;">' +
        '<h1 style="margin: 0; color: #286581; font-size: 22px; text-transform: none;"><i class="fas fa-user-plus" style="color: #05817d;"></i> Nuevo Cliente</h1>' +
        '<a href="#" onclick="coloseModal();" style="color: #666; font-size: 18px;"><i class="fas fa-times-circle"></i></a>' +
        '</div>' +
        '<form name="form_nuevo_cliente_modal" id="form_nuevo_cliente_modal" onsubmit="event.preventDefault(); registrarClienteModal();">' +
        '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">' +
        '<div>' +
        '<label>Ced./RUC (Obligatorio):</label>' +
        '<input type="text" name="nit_modal" id="nit_modal" class="modal-search-input" placeholder="Ruc o Cedula" required>' +
        '</div>' +
        '<div>' +
        '<label>Nombre Completo:</label>' +
        '<input type="text" name="nombre_modal" id="nombre_modal" class="modal-search-input" placeholder="Nombre completo" required>' +
        '</div>' +
        '<div>' +
        '<label>Teléfono:</label>' +
        '<input type="number" name="telefono_modal" id="telefono_modal" class="modal-search-input" placeholder="Teléfono" min="0" required>' +
        '</div>' +
        '<div>' +
        '<label>Dirección:</label>' +
        '<input type="text" name="direccion_modal" id="direccion_modal" class="modal-search-input" placeholder="Dirección" required>' +
        '</div>' +
        '</div>' +
        '<div class="alert alertAddProduct"></div>' +
        '<div style="text-align: right; margin-top: 20px;">' +
        '<a href="#" class="btn_ok" style="background: #e74c3c; padding: 8px 20px; border-radius: 4px; margin-right: 10px;" onclick="coloseModal();"><i class="fas fa-ban"></i> Cancelar</a>' +
        '<button type="submit" class="btn_new" style="background: #05817d; padding: 8px 25px; border-radius: 4px; color: #fff; border: none; cursor: pointer;"><i class="fas fa-save"></i> Guardar Cliente</button>' +
        '</div>' +
        '</form>' +
        '</div>');

    $('.modal').fadeIn();
    $('#nit_modal').focus();
}

function registrarClienteModal() {
    var nit = $('#nit_modal').val();
    var nombre = $('#nombre_modal').val();
    var telefono = $('#telefono_modal').val();
    var direccion = $('#direccion_modal').val();

    if (nit == '' || nombre == '') {
        $('.alertAddProduct').html('<p style="color:red;">El RUC y Nombre son obligatorios.</p>');
        return false;
    }

    $.ajax({
        url: 'ajax.php',
        type: "POST",
        async: true,
        data: {
            action: 'registroClienteModal',
            nit: nit,
            nombre: nombre,
            telefono: telefono,
            direccion: direccion
        },
        success: function (response) {
            if (response != 'error') {
                var info = JSON.parse(response);
                if (info.codcliente > 0) {
                    // Cargar datos en el formulario de venta
                    $('#idcliente').val(info.codcliente);
                    $('#nom_cliente').val(info.nombre);
                    $('#nit_cliente').val(info.nit);
                    $('#tel_cliente').val(info.telefono);
                    $('#dir_cliente').val(info.direccion);

                    // Desactivar campos
                    $('#nit_cliente').attr('disabled', 'disabled');
                    $('#tel_cliente').attr('disabled', 'disabled');
                    $('#dir_cliente').attr('disabled', 'disabled');
                    $('.btn_new_cliente').slideUp();

                    coloseModal();
                } else {
                    $('.alertAddProduct').html('<p style="color:red;">' + info.msg + '</p>');
                }
            } else {
                $('.alertAddProduct').html('<p style="color:red;">Error al registrar el cliente.</p>');
            }
        },
        error: function (error) {
            console.log(error);
        }
    });
}

// --- REGISTRO DE PROVEEDOR DESDE MODAL ---
function abrirModalNuevoProveedor() {
    $('.bodyModal').html('<div class="modal-search-container">' +
        '<div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #05817d; margin-bottom: 20px; padding-bottom: 10px;">' +
        '<h1 style="margin: 0; color: #286581; font-size: 22px; text-transform: none;"><i class="fas fa-truck-loading" style="color: #05817d;"></i> Nuevo Proveedor</h1>' +
        '<a href="#" onclick="coloseModal();" style="color: #666; font-size: 18px;"><i class="fas fa-times-circle"></i></a>' +
        '</div>' +
        '<form name="form_nuevo_proveedor_modal" id="form_nuevo_proveedor_modal" onsubmit="event.preventDefault(); registrarProveedorModal();">' +
        '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">' +
        '<div>' +
        '<label>Nombre Proveedor:</label>' +
        '<input type="text" name="proveedor_modal" id="proveedor_modal" class="modal-search-input" placeholder="Nombre de empresa" required>' +
        '</div>' +
        '<div>' +
        '<label>Contacto:</label>' +
        '<input type="text" name="contacto_modal" id="contacto_modal" class="modal-search-input" placeholder="Nombre de contacto" required>' +
        '</div>' +
        '<div>' +
        '<label>Teléfono:</label>' +
        '<input type="number" name="telefono_prov_modal" id="telefono_prov_modal" class="modal-search-input" placeholder="Teléfono" min="0" required>' +
        '</div>' +
        '<div>' +
        '<label>Dirección:</label>' +
        '<input type="text" name="direccion_prov_modal" id="direccion_prov_modal" class="modal-search-input" placeholder="Dirección" required>' +
        '</div>' +
        '</div>' +
        '<div class="alert alertAddProduct"></div>' +
        '<div style="text-align: right; margin-top: 20px;">' +
        '<a href="#" class="btn_ok" style="background: #e74c3c; padding: 8px 20px; border-radius: 4px; margin-right: 10px;" onclick="coloseModal();"><i class="fas fa-ban"></i> Cancelar</a>' +
        '<button type="submit" class="btn_new" style="background: #05817d; padding: 8px 25px; border-radius: 4px; color: #fff; border: none; cursor: pointer;"><i class="fas fa-save"></i> Guardar Proveedor</button>' +
        '</div>' +
        '</form>' +
        '</div>');

    $('.modal').fadeIn();
    $('#proveedor_modal').focus();
}

function registrarProveedorModal() {
    var proveedor = $('#proveedor_modal').val();
    var contacto = $('#contacto_modal').val();
    var telefono = $('#telefono_prov_modal').val();
    var direccion = $('#direccion_prov_modal').val();

    if (proveedor == '' || contacto == '') {
        $('.alertAddProduct').html('<p style="color:red;">El Proveedor y Contacto son obligatorios.</p>');
        return false;
    }

    $.ajax({
        url: 'ajax.php',
        type: "POST",
        async: true,
        data: {
            action: 'registroProveedorModal',
            proveedor: proveedor,
            contacto: contacto,
            telefono: telefono,
            direccion: direccion
        },
        success: function (response) {
            if (response != 'error') {
                var info = JSON.parse(response);
                if (info.codproveedor > 0) {
                    // Cargar datos en el formulario de compra
                    $('#idproveedor').val(info.codproveedor);
                    $('#nom_proveedor').val(info.proveedor);
                    $('#tel_proveedor').val(info.telefono);
                    $('#dir_proveedor').val(info.direccion);

                    $('.btn_new_proveedor').slideUp();

                    coloseModal();
                } else {
                    $('.alertAddProduct').html('<p style="color:red;">' + info.msg + '</p>');
                }
            } else {
                $('.alertAddProduct').html('<p style="color:red;">Error al registrar el proveedor.</p>');
            }
        },
        error: function (error) {
            console.log(error);
        }
    });
}

// -----------------------------------------
// STOCK ADJUSTMENT JS
// -----------------------------------------

$(document).ready(function () {
    // Search Product for Adjustment
    $('#txt_producto_ajuste').keypress(function (e) {
        if (e.which == 13) {
            e.preventDefault();
            var producto = $(this).val();
            var action = 'infoProductoStock'; // Custom action for flexible search

            if (producto != '') {
                $.ajax({
                    url: 'ajax.php',
                    type: "POST",
                    async: true,
                    data: { action: action, producto: producto },

                    success: function (response) {
                        if (response != 'error') {
                            var info = JSON.parse(response);
                            $('#producto_id_ajuste').val(info.codproducto);
                            $('#info_producto_ajuste').html('[' + info.codigo + '] ' + info.descripcion);
                            $('#stock_actual_ajuste').html(info.existencia);
                            $('#cantidad_ajuste').focus();
                        } else {
                            $('#producto_id_ajuste').val('');
                            $('#info_producto_ajuste').html('Producto no encontrado');
                            $('#stock_actual_ajuste').html('-');
                        }
                    },
                    error: function (error) {
                    }
                });
            }
        }
    });

    // Load History
    if ($('#historialAjustes').length > 0) {
        listaHistorialAjustes();
    }

    // Search in History
    $('#busqueda_historial').keyup(function (e) {
        e.preventDefault();
        var busqueda = $(this).val();
        listaHistorialAjustes(busqueda, 1);
    });

});

function listaHistorialAjustes(busqueda = '', pagina = 1) {
    $.ajax({
        url: 'ajax.php',
        type: "POST",
        async: true,
        data: {
            action: 'listaHistorialAjustes',
            busqueda: busqueda,
            pagina: pagina
        },
        success: function (response) {
            console.log(response); // Debug
            if (response != 'error') {
                var info = JSON.parse(response);
                $('#historialAjustes').html(info.tabla);
                $('.paginador_ajuste').html(info.paginador);
            }
        },
        error: function (error) {
            console.log(error);
        }
    });
}

function procesarAjuste() {
    var producto_id = $('#producto_id_ajuste').val();
    var cantidad = $('#cantidad_ajuste').val();
    var tipo = $('#tipo_ajuste').val();
    var motivo_id = $('#motivo_ajuste').val();
    var nota = $('#nota_ajuste').val();
    var action = 'procesarAjuste';

    if (producto_id == '' || cantidad == '' || tipo == '' || motivo_id == '') {
        alert('Todos los campos son obligatorios');
        return false;
    }

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: {
            action: action,
            producto_id: producto_id,
            cantidad: cantidad,
            tipo: tipo,
            motivo_id: motivo_id,
            nota: nota
        },
        success: function (response) {
            var info = JSON.parse(response);
            if (info.cod == '00') {
                alert(info.msg);
                // Reset form
                $('#txt_producto_ajuste').val('');
                $('#producto_id_ajuste').val('');
                $('#info_producto_ajuste').html('-');
                $('#stock_actual_ajuste').html('-');
                $('#cantidad_ajuste').val('');
                $('#nota_ajuste').val('');
                listaHistorialAjustes();
            } else {
                alert(info.msg);
            }
        },
        error: function (error) {
            console.log(error);
        }
    });
}

// Ver detalle de venta rápido (Expandir fila)
function verDetalleVentaRapido(noventa, el) {
    var tr = $(el).parents('tr');
    var nextTr = tr.next('.detalle_rapido_venta');

    if (nextTr.length > 0) {
        nextTr.toggle();
        var icon = $(el).find('i');
        if (nextTr.is(':visible')) {
            icon.removeClass('fa-plus-circle').addClass('fa-minus-circle').css('color', '#e74c3c');
        } else {
            icon.removeClass('fa-minus-circle').addClass('fa-plus-circle').css('color', '#2980b9');
        }
    } else {
        var action = 'getDetalleVenta';
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            async: true,
            data: { action: action, noventa: noventa },
            success: function (response) {
                if (response != 'error') {
                    var colspan = tr.find('td').length;
                    var newRow = '<tr class="detalle_rapido_venta" style="background: #fff;"><td colspan="' + colspan + '" style="padding: 0 20px 20px 40px;">' + response + '</td></tr>';
                    tr.after(newRow);
                    $(el).find('i').removeClass('fa-plus-circle').addClass('fa-minus-circle').css('color', '#e74c3c');
                }
            },
            error: function (error) {
                console.log(error);
            }
        });
    }
}

/* ========================================================
   FUNCIONES DE CÓDIGO DE BARRAS E IMPRESIÓN DE ETIQUETAS
   ======================================================== */

function getBarcodeFormat(code) {
    var cleanCode = String(code || '').trim();
    if (/^\d{13}$/.test(cleanCode)) {
        return "EAN13";
    }
    if (/^\d{8}$/.test(cleanCode)) {
        return "EAN8";
    }
    return "CODE128";
}

function updateBarcodePreview(code, previewSvgId) {
    var preview = $('#' + previewSvgId);
    if (!preview.length) return;
    var strCode = String(code || '').trim();
    if (strCode.length > 0) {
        try {
            var formatToUse = getBarcodeFormat(strCode);
            JsBarcode(preview[0], strCode, {
                format: formatToUse,
                width: 2,
                height: 50,
                fontSize: 14,
                margin: 10,
                flat: false,
                displayValue: true
            });
            preview.show();
        } catch (e) {
            console.log("Error generando barcode preview:", e);
            preview.hide();
        }
    } else {
        preview.hide();
    }
}

function generarCodigoForm(inputId, previewSvgId) {
    var input = $('#' + inputId);
    var preview = $('#' + previewSvgId);
    if (!input.length) {
        console.error('No se encontró el campo del código de producto:', inputId);
        return;
    }

    if (typeof JsBarcode !== 'function') {
        alert('No se pudo cargar el generador de códigos de barras. Recarga la página e inténtalo de nuevo.');
        return;
    }

    $.ajax({
        url: 'ajax.php',
        type: 'POST',
        data: { action: 'generarCodigo' },
        success: function(resp) {
            try {
                var res = (typeof resp === 'string') ? JSON.parse(resp) : resp;
                if (res.status === 'success') {
                    input.val(res.codigo).trigger('input');
                    updateBarcodePreview(res.codigo, previewSvgId);
                } else {
                    preview.hide();
                    alert(res.message || 'No se pudo generar el código del producto.');
                }
            } catch(e) {
                console.error("Error parseando respuesta generarCodigo:", e);
                preview.hide();
                alert('El servidor devolvió una respuesta inválida al generar el código.');
            }
        },
        error: function(xhr) {
            console.error('Error solicitando código de producto:', xhr.status, xhr.responseText);
            preview.hide();
            alert('No se pudo conectar con el generador de códigos de barras.');
        }
    });
}

function modalImprimirBarcode(id, codigo, descripcion, precio, codigo_interno) {
    codigo_interno = codigo_interno || '';
    var precioFormatted = (typeof formatCant === 'function') ? formatCant(precio) : precio;
    var codIntInfo = (codigo_interno && codigo_interno !== 'null' && codigo_interno !== 'undefined') ? '<p style="margin:3px 0;"><strong>Código Interno:</strong> <span style="font-family:monospace; background:#e2e8f0; padding:2px 6px; border-radius:4px; font-weight:bold;">' + codigo_interno + '</span></p>' : '';
    
    var htmlModal = '<form action="" method="post" name="form_print_barcode" id="form_print_barcode" class="form_print_barcode" onsubmit="event.preventDefault(); ejecutarImpresionEtiquetas(\'' + addslashesJS(codigo) + '\', \'' + addslashesJS(descripcion) + '\', \'' + addslashesJS(precioFormatted) + '\', \'' + addslashesJS(codigo_interno) + '\');">' +
        '<h1><i class="fas fa-barcode" style="font-size: 35pt;"></i> <br> Imprimir Código de Barras</h1>' +
        '<div style="background:#f8f9fa; border:1px solid #e9ecef; padding:10px; border-radius:6px; margin:10px 0; text-align:left; font-size:13px; color:#333;">' +
        '<p style="margin:3px 0;"><strong>Producto:</strong> ' + descripcion + '</p>' +
        '<p style="margin:3px 0;"><strong>Código:</strong> <span style="font-family:monospace; background:#e2e8f0; padding:2px 6px; border-radius:4px; font-weight:bold;">' + codigo + '</span></p>' +
        codIntInfo +
        '<p style="margin:3px 0;"><strong>Precio:</strong> Gs. ' + precioFormatted + '</p>' +
        '</div>' +
        '<div class="barcode-modal-preview" style="margin:10px 0; text-align:center;">' +
        '<canvas id="modalBarcodeCanvas" aria-label="Código de barras del producto" style="max-width:100%; height:auto; margin:0 auto; display:block;"></canvas>' +
        '</div>' +
        '<div style="display:flex; gap:6px; margin-bottom:10px; text-align:left;">' +
        '<div style="flex:1;">' +
        '<label style="font-weight:bold; font-size:11px; display:block; margin-bottom:3px;">Cantidad:</label>' +
        '<input type="number" id="lblCantidad" value="2" min="1" max="200" required style="width:100%; text-align:center; height:35px; font-size:14px; margin-bottom:0;">' +
        '</div>' +
        '<div style="flex:1.3;">' +
        '<label style="font-weight:bold; font-size:11px; display:block; margin-bottom:3px;">Tamaño / Papel:</label>' +
        '<select id="lblFormato" style="width:100%; height:35px; font-size:12px; margin-bottom:0; padding:2px 4px;">' +
        '<option value="100mm">Rollo 100mm (FTX TDE010U / 4")</option>' +
        '<option value="80mm">Rollo 80mm</option>' +
        '<option value="58mm">Rollo 58mm</option>' +
        '<option value="60x40">Térmica 60x40mm (Centrado FTX 010)</option>' +
        '<option value="55x30" selected>Térmica 5.5x30mm (Centrado FTX 010)</option>' +
        '<option value="50x30">Térmica 50x30mm (Centrado FTX 010)</option>' +
        '<option value="40x25">Térmica 40x25mm (Centrado FTX 010)</option>' +
        '</select>' +
        '</div>' +
        '<div style="flex:1.2;">' +
        '<label style="font-weight:bold; font-size:11px; display:block; margin-bottom:3px;">Por Fila (Ancho):</label>' +
        '<select id="lblColumnas" style="width:100%; height:35px; font-size:12px; margin-bottom:0; padding:2px 4px;">' +
        '<option value="1" selected>1 por fila (Grande/Individual)</option>' +
        '<option value="2">2 por fila (50mm cada una)</option>' +
        '<option value="3">3 por fila (33mm cada una)</option>' +
        '<option value="4">4 por fila (25mm cada una)</option>' +
        '</select>' +
        '</div>' +
        '</div>' +
        '<div style="display:flex; justify-content:space-around; margin-bottom:12px; text-align:left; font-size:12px; background:#f1f5f9; padding:6px; border-radius:4px; flex-wrap:wrap; gap:8px;">' +
        '<label style="cursor:pointer; display:inline-flex; align-items:center; gap:4px;"><input type="checkbox" id="chkVerNombre" checked style="width:auto; height:auto; margin:0;"> Incluir Nombre</label>' +
        '<label style="cursor:pointer; display:inline-flex; align-items:center; gap:4px;"><input type="checkbox" id="chkVerPrecio" checked style="width:auto; height:auto; margin:0;"> Incluir Precio</label>' +
        '<label style="cursor:pointer; display:inline-flex; align-items:center; gap:4px;"><input type="checkbox" id="chkVerCodInterno" ' + ((codigo_interno && codigo_interno !== 'null' && codigo_interno !== 'undefined') ? 'checked' : '') + ' style="width:auto; height:auto; margin:0;"> Incluir Cód. Interno</label>' +
        '</div>' +
        '<div class="alert alertPrintBC"></div>' +
        '<a href="#" class="btn_ok closeModal" onclick="coloseModal();" style="margin-right:10px;"><i class="fas fa-ban"></i> Cerrar</a>' +
        '<button type="submit" class="btn_new" style="background:#27ae60; border-color:#27ae60; cursor:pointer;"><i class="fas fa-print"></i> Imprimir Código</button>' +
        '</form>';

    $('.bodyModal').html(htmlModal);
    $('.modal').fadeIn();

    // Auto-select columns depending on formatting selection to avoid printing issues on individual labels
    $('#lblFormato').on('change', function() {
        var val = $(this).val();
        if (val === '100mm') {
            $('#lblColumnas').val('2');
        } else if (val === '80mm' || val === '58mm' || val === '60x40' || val === '55x30' || val === '50x30' || val === '40x25') {
            $('#lblColumnas').val('1');
        }
    });

        setTimeout(function() {
        var barcodeCanvas = document.getElementById("modalBarcodeCanvas");
        if (barcodeCanvas && codigo) {
            try {
                JsBarcode(barcodeCanvas, String(codigo).trim(), {
                     format: getBarcodeFormat(codigo),
                     width: 3,
                     height: 65,
                     fontSize: 16,
                     margin: 10,
                     displayValue: true
                });
            } catch(e) {
                console.error("Error al renderizar modalBarcodeCanvas:", e);
            }
        }
    }, 100);
}

function addslashesJS(str) {
    return (str + '').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');
}

function ejecutarImpresionEtiquetas(codigo, descripcion, precio, codigo_interno) {
    var cantidad = parseInt($('#lblCantidad').val()) || 1;
    var formato = $('#lblFormato').val();
    var columnas = parseInt($('#lblColumnas').val()) || 1;
    var verNombre = $('#chkVerNombre').is(':checked');
    var verPrecio = $('#chkVerPrecio').is(':checked');
    var verCodInterno = $('#chkVerCodInterno').is(':checked');
    var strCodigo = String(codigo || '').trim();
    codigo_interno = String(codigo_interno || '').trim();

    // Determinar el ancho de la etiqueta en mm para calcular el tamaño del código de barras
    var labelWidthMM = 100; // default
    if (formato === '60x40' || formato === '60x40_p' || formato === '60x40_l') {
        labelWidthMM = 55;
    } else if (formato === '55x30' || formato === '55x30_p' || formato === '55x30_l') {
        labelWidthMM = 50;
    } else if (formato === '50x30' || formato === '50x30_p' || formato === '50x30_l') {
        labelWidthMM = 45;
    } else if (formato === '40x25' || formato === '40x25_p' || formato === '40x25_l') {
        labelWidthMM = 35;
    } else if (formato === '80mm') {
        labelWidthMM = 75;
    } else if (formato === '58mm') {
        labelWidthMM = 53;
    }

    if (columnas > 1) {
        labelWidthMM = Math.floor(labelWidthMM / columnas) - 2;
    }

    var numDigits = strCodigo.length;
    var modules = (11 * numDigits) + 35 + 20;
    var availableWidthPx = labelWidthMM * 3.78;
    var barWidth = Math.floor((availableWidthPx / modules) * 10) / 10;
    barWidth = Math.max(1.0, Math.min(barWidth, 2.5));

    var barHeight = 60;
    var fontSize = 14;
    if (labelWidthMM < 40) {
        barHeight = 45;
        fontSize = 11;
    } else if (labelWidthMM < 55) {
        barHeight = 55;
        fontSize = 12;
    }

    var styleDimension = '@page { margin: 0; } body { margin: 0; padding: 0; }';

    var tempCanvas = document.createElement("canvas");
    try {
        JsBarcode(tempCanvas, strCodigo, {
            format: "CODE128",
            width: 3,
            height: 100,
            fontSize: 20,
            margin: 10,
            displayValue: true,
            background: "#ffffff",
            lineColor: "#000000"
        });
    } catch(e) {
        console.error("Error al generar barcode en canvas:", e);
    }
    var barcodePngDataUrl = tempCanvas.toDataURL("image/png");

    var maxBarcodeHeightStyle = 'height:auto;';
    if (formato === '55x30' || formato === '55x30_p' || formato === '55x30_l' || formato === '50x30' || formato === '50x30_p' || formato === '50x30_l') {
        maxBarcodeHeightStyle = 'max-height:14mm; height:auto;';
    } else if (formato === '40x25' || formato === '40x25_p' || formato === '40x25_l') {
        maxBarcodeHeightStyle = 'max-height:11mm; height:auto;';
    }

    var barcodeImgTag = '<img src="' + barcodePngDataUrl + '" style="display:block; margin:0 auto; max-width:92%; ' + maxBarcodeHeightStyle + '" />';

    var itemWidthStyle = 'width: 98%; margin: 1% auto;';
    if (formato === '60x40' || formato === '60x40_p' || formato === '60x40_l') {
        itemWidthStyle = 'width: 60mm; height: 38mm; margin: 0 auto; page-break-after: always;';
    } else if (formato === '55x30' || formato === '55x30_p' || formato === '55x30_l') {
        itemWidthStyle = 'width: 55mm; height: 28mm; margin: 0 auto; page-break-after: always;';
    } else if (formato === '50x30' || formato === '50x30_p' || formato === '50x30_l') {
        itemWidthStyle = 'width: 50mm; height: 28mm; margin: 0 auto; page-break-after: always;';
    } else if (formato === '40x25' || formato === '40x25_p' || formato === '40x25_l') {
        itemWidthStyle = 'width: 40mm; height: 23mm; margin: 0 auto; page-break-after: always;';
    } else {
        if (columnas === 2) {
            itemWidthStyle = 'width: 48%; margin: 1%;';
        } else if (columnas === 3) {
            itemWidthStyle = 'width: 31%; margin: 1%;';
        } else if (columnas === 4) {
            itemWidthStyle = 'width: 23%; margin: 1%;';
        }
    }

    var labelItemsHtml = '<div style="display:flex; flex-wrap:wrap; width:100%; box-sizing:border-box; align-content:flex-start; justify-content:flex-start;">';
    for (var i = 0; i < cantidad; i++) {
        labelItemsHtml += '<div style="' + itemWidthStyle + ' padding:1px; box-sizing:border-box; font-family:Arial,sans-serif; text-align:center; display:flex; flex-direction:column; justify-content:center; align-items:center; page-break-inside:avoid; border: none; overflow:hidden;">';
        if (verNombre) {
            labelItemsHtml += '<div style="font-size: 9px; font-weight: bold; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; margin-bottom: 1px; color: #000; line-height:1.1;">' + descripcion + '</div>';
        }
        if (verCodInterno && codigo_interno && codigo_interno !== 'null' && codigo_interno !== 'undefined') {
            labelItemsHtml += '<div style="font-size: ' + Math.max(8, fontSize - 2) + 'px; font-weight: bold; color: #444; margin-bottom: 1px;">Ref: ' + codigo_interno + '</div>';
        }
        labelItemsHtml += barcodeImgTag;
        if (verPrecio) {
            labelItemsHtml += '<div style="font-size: ' + (fontSize + 1) + 'px; font-weight: bold; margin-top: 1px; color: #000; line-height:1.1;">Gs. ' + precio + '</div>';
        }
        labelItemsHtml += '</div>';
    }
    labelItemsHtml += '</div>';

    var iframe = document.getElementById("printIframeBarcode");
    if (iframe && iframe.parentNode) {
        iframe.parentNode.removeChild(iframe);
    }
    iframe = document.createElement("iframe");
    iframe.id = "printIframeBarcode";
    iframe.style.position = "fixed";
    iframe.style.left = "-9999px";
    iframe.style.top = "-9999px";
    iframe.style.width = "800px";
    iframe.style.height = "600px";
    iframe.style.border = "0";
    document.body.appendChild(iframe);

    var doc = iframe.contentWindow.document;
    doc.open();
    doc.write('<!DOCTYPE html><html><head><title>Imprimir Etiquetas</title><style>' +
        'html, body { margin: 0; padding: 0; background: #fff; color: #000; } ' +
        styleDimension +
        'img { display: block; margin: 0 auto; -webkit-print-color-adjust: exact; print-color-adjust: exact; } ' +
        '</style></head><body>' + labelItemsHtml + '</body></html>');
    doc.close();

    setTimeout(function() {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
    }, 400);
}

// Funciones globales para crear Categorias, Marcas y Tamaños sobre la marcha
function nuevaCategoriaRapida(selectId = 'cm-categoria') {
    let nombre = prompt("Ingrese el nombre de la nueva Categoría:");
    if (!nombre || nombre.trim() === "") return;
    $.ajax({
        url: 'ajax_categorias_marcas_tamanos.php',
        type: 'POST',
        data: { action: 'crearCategoriaRapido', nombre: nombre.trim() },
        success: function(response) {
            let info = JSON.parse(response);
            if(info.cod == "00") {
                let sel = document.getElementById(selectId);
                if (sel) {
                    let opt = document.createElement('option');
                    opt.value = info.idcategoria;
                    opt.innerHTML = info.nombre;
                    opt.selected = true;
                    sel.appendChild(opt);
                    if ($(sel).hasClass('select2-hidden-accessible')) {
                        $(sel).trigger('change.select2');
                    } else {
                        $(sel).trigger('change');
                    }
                }
            } else {
                alert("Error al crear la categoría: " + info.msg);
            }
        }
    });
}

function nuevaMarcaRapida(selectId = 'cm-marca') {
    let nombre = prompt("Ingrese el nombre de la nueva Marca:");
    if (!nombre || nombre.trim() === "") return;
    $.ajax({
        url: 'ajax_categorias_marcas_tamanos.php',
        type: 'POST',
        data: { action: 'crearMarcaRapido', nombre: nombre.trim() },
        success: function(response) {
            let info = JSON.parse(response);
            if(info.cod == "00") {
                let sel = document.getElementById(selectId);
                if (sel) {
                    let opt = document.createElement('option');
                    opt.value = info.idmarca;
                    opt.innerHTML = info.nombre;
                    opt.selected = true;
                    sel.appendChild(opt);
                    if ($(sel).hasClass('select2-hidden-accessible')) {
                        $(sel).trigger('change.select2');
                    } else {
                        $(sel).trigger('change');
                    }
                }
            } else {
                alert("Error al crear la marca: " + info.msg);
            }
        }
    });
}

function nuevoTamanoRapido(selectId = 'cm-tamano') {
    let nombre = prompt("Ingrese el nombre del nuevo Tamaño:");
    if (!nombre || nombre.trim() === "") return;
    $.ajax({
        url: 'ajax_categorias_marcas_tamanos.php',
        type: 'POST',
        data: { action: 'crearTamanoRapido', nombre: nombre.trim() },
        success: function(response) {
            let info = JSON.parse(response);
            if(info.cod == "00") {
                let sel = document.getElementById(selectId);
                if (sel) {
                    let opt = document.createElement('option');
                    opt.value = info.idtamano;
                    opt.innerHTML = info.nombre;
                    opt.selected = true;
                    sel.appendChild(opt);
                    if ($(sel).hasClass('select2-hidden-accessible')) {
                        $(sel).trigger('change.select2');
                    } else {
                        $(sel).trigger('change');
                    }
                }
            } else {
                alert("Error al crear el tamaño: " + info.msg);
            }
        }
    });
}
