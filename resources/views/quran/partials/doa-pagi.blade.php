<style>
    .doa-pagi-trigger {
        position: fixed;
        right: 18px;
        bottom: 88px;
        z-index: 1035;
        border-radius: 999px;
        padding: 10px 16px;
        box-shadow: 0 8px 24px rgba(13, 110, 253, 0.25);
    }
    .doa-pagi-modal .modal-body {
        max-height: 70vh;
        overflow-y: auto;
    }
    .doa-pagi-text {
        font-family: 'Scheherazade New', serif;
        font-size: 1.45rem;
        line-height: 2.4rem;
        text-align: right;
        direction: rtl;
        white-space: pre-line;
    }
</style>

<button
    type="button"
    id="doaPagiTrigger"
    class="btn btn-primary doa-pagi-trigger"
    data-toggle="modal"
    data-target="#doaPagiModal"
    data-bs-toggle="modal"
    data-bs-target="#doaPagiModal">
    Doa Pagi
</button>

<div class="modal fade doa-pagi-modal" id="doaPagiModal" tabindex="-1" aria-labelledby="doaPagiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="doaPagiModalLabel">Doa Pagi</h5>
                <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="doa-pagi-text">
بِسْمِ اللَّهِ الرَّحْمٰنِ الرَّحِيمِ  
الْحَمْدُ لِلَّهِ رَبِّ الْعَالَمِينَ. اللَّهُمَّ صَلِّ عَلَىٰ مُحَمَّدٍ وَعَلَىٰ آلِهِ وَأَصْحَابِهِ أَجْمَعِينَ.

أَشْهَدُ أَنْ لَا إِلٰهَ إِلَّا اللَّهُ وَأَشْهَدُ أَنَّ مُحَمَّدًا عَبْدُهُ وَرَسُولُهُ.

رَضِيتُ بِاللَّهِ رَبًّا، وَبِالإِسْلَامِ دِينًا، وَبِمُحَمَّدٍ نَبِيًّا وَرَسُولًا.

اللَّهُمَّ أَنْتَ رَبِّي، لَا إِلٰهَ إِلَّا أَنْتَ، خَلَقْتَنِي وَأَنَا عَبْدُكَ، وَأَنَا عَلَىٰ عَهْدِكَ وَوَعْدِكَ مَا اسْتَطَعْتُ،
أَعُوذُ بِكَ مِنْ شَرِّ مَا صَنَعْتُ، أَبُوءُ لَكَ بِنِعْمَتِكَ عَلَيَّ، وَأَبُوءُ بِذَنْبِي، فَاغْفِرْ لِي،
فَإِنَّهُ لَا يَغْفِرُ الذُّنُوبَ إِلَّا أَنْتَ.

اللَّهُمَّ إِنِّي أَسْأَلُكَ عِلْمًا نَافِعًا، وَرِزْقًا وَاسِعًا، وَعَمَلًا مُتَقَبَّلًا،
وَشِفَاءً مِنْ كُلِّ دَاءٍ،
وَأَعُوذُ بِكَ مِنَ الْعَجْزِ وَالْكَسَلِ، وَالْجُبْنِ وَالْبُخْلِ، وَالْهَرَمِ، وَعَذَابِ الْقَبْرِ.

اللَّهُمَّ إِنِّي أَعُوذُ بِكَ مِنَ الْهَمِّ وَالْحَزَنِ،
وَالْعَجْزِ وَالْكَسَلِ،
وَالْجُبْنِ وَالْبُخْلِ،
وَضَلَعِ الدَّيْنِ وَغَلَبَةِ الرِّجَالِ.

اللَّهُمَّ أَرِنَا الْحَقَّ حَقًّا وَارْزُقْنَا اتِّبَاعَهُ،
وَأَرِنَا الْبَاطِلَ بَاطِلًا وَارْزُقْنَا اجْتِنَابَهُ.

اللَّهُمَّ إِنِّي أَعُوذُ بِكَ مِنْ زَوَالِ نِعْمَتِكَ،
وَتَحَوُّلِ عَافِيَتِكَ،
وَفُجَاءَةِ نِقْمَتِكَ،
وَجَمِيعِ سَخَطِكَ.

رَبَّنَا آتِنَا فِي الدُّنْيَا حَسَنَةً،
وَفِي الآخِرَةِ حَسَنَةً،
وَقِنَا عَذَابَ النَّارِ.
</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const trigger = document.getElementById("doaPagiTrigger");
    if (!trigger || typeof $ === "undefined") {
        return;
    }

    trigger.addEventListener("click", function () {
        $("#doaPagiModal").modal("show");
    });
});
</script>
