<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: backend/login.php");
    exit;
}
?>
<div id="reservationModal" class="modal">
    <div class="modal-content reservation-modal">
        <button type="button" class="modal-close" id="closeReservationModal">&times;</button>
        <h3 class="modal-title">Fazer Reserva</h3>

        <div id="reservationForm">
            <div class="form-row">
                <div class="form-group">
                    <label>Check-in</label>
                    <input type="text" id="modalCheckinDate" class="date-input" placeholder="Data de entrada">
                </div>
                <div class="form-group">
                    <label>Check-out</label>
                    <input type="text" id="modalCheckoutDate" class="date-input" placeholder="Data de saída">
                </div>
            </div>

            <div class="form-group">
                <label>Número de Hóspedes</label>
                <select id="modalNumGuests" class="date-input">
                    <option value="1">1 pessoa</option>
                    <option value="2" selected>2 pessoas</option>
                    <option value="3">3 pessoas</option>
                    <option value="4">4 pessoas</option>
                    <option value="5">5 pessoas</option>
                    <option value="6">6 pessoas</option>
                </select>
            </div>

            <div id="modalReservationSummary" class="reservation-summary" style="display: none;">
                <!-- Resumo da reserva será carregado aqui -->
            </div>



            <button class="btn-reservar" id="btnModalReservar">Reservar Agora</button>
        </div>

        <div id="reservationSuccess" style="display: none;">
            <div class="success-message">
                <i class="fas fa-check-circle" style="font-size: 3em; color: #28a745;"></i>
                <h4>Reserva Confirmada!</h4>
                <p id="reservationSuccessText">Sua reserva foi criada com sucesso. Você receberá um email de confirmação em breve.</p>
                <button class="btn-reservar" onclick="closeReservationModal()">Fechar</button>
            </div>


            <style>
                .reservation-modal {
                    max-width: 600px;
                    width: 90%;
                }

                .success-message {
                    text-align: center;
                    padding: 40px 20px;
                }

                .success-message i {
                    margin-bottom: 20px;
                }
            </style>