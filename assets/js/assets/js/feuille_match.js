document.querySelectorAll('.player-select').forEach(select => {
    select.addEventListener('change', () => {

        const data = JSON.parse(select.dataset.player);
        const infoBox = select.parentElement.querySelector('.player-info');

        let html = `
            <div class="info-box">
                <strong>${data.nom} ${data.prenom}</strong><br>
                Taille : ${data.taille} cm<br>
                Poids : ${data.poids} kg<br>
                Note moyenne : ${data.moyenne ?? "Aucune"}<br><br>
        `;

        // ===== Dernières évaluations =====
        html += "<strong>Dernières évaluations :</strong><br>";

        if (!data.evaluations || data.evaluations.length === 0) {
            html += "<i>Aucune évaluation</i><br><br>";
        } else {
            data.evaluations.forEach(ev => {
                html += `• ${ev.evaluation} ⭐ (${ev.date_heure})<br>`;
            });
            html += "<br>";
        }

        // ===== Derniers commentaires =====
        html += "<strong>Derniers commentaires :</strong><br>";

        if (!data.commentaires || data.commentaires.length === 0) {
            html += "<i>Aucun commentaire</i>";
        } else {
            data.commentaires.forEach(c => {
                html += `• ${c.commentaire}<br>`;
            });
        }

        html += "</div>";

        infoBox.innerHTML = html;
    });
});
