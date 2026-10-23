    var div=document.getElementById("is_form");

    function Form(){
        if(!div) return;

        if (div.classList.contains('show')) {
            div.classList.remove('show');
        } else {
            div.classList.add('show');
        }
    }
    var div2=document.getElementById("status");
    var display=0;

    function displays(){
        if(display==1){
            div2.style.display='block';
            display=0;
        }else{
            div2.style.display='none';
            display=1;
        }
    }
    var div3=document.getElementById("to_report");
    var display=0;

    function report(){
        if(display==1){
            div3.style.display='block';
            display=0;
        }else{
            div3.style.display='none';
            display=1;
        }
    }

    var div4=document.getElementById("tracks");

    function track(){
        if(!div4) return;

        div4.style.display = div4.style.display === 'block' ? 'none' : 'block';
    }

    function toggleDropdown(){
        document.getElementById("dropdownMenu").classList.toggle('show');
    }

    function assigning(btn){
        const forms = document.querySelectorAll('.assign-form');
        const form = btn.nextElementSibling;
        if (!form) return;

        let shouldOpen = form.style.display !== 'block';
        forms.forEach(function(item){
            item.style.display = 'none';
        });

        if (shouldOpen) {
            form.style.display = 'block';
        }
    }

    document.addEventListener('DOMContentLoaded', function(){
        document.querySelectorAll('.assign-form').forEach(function(form){
            form.style.display = 'none';
        });

        const statusForm = document.getElementById('statusForm');
        const status = document.getElementById('status');

        if (statusForm && status) {
            statusForm.addEventListener('submit', function(event){
                event.preventDefault();

                const submitButton = statusForm.querySelector('button[type="submit"]');
                const formData = new FormData(statusForm);
                submitButton.disabled = true;
                submitButton.textContent = 'Checking...';

                fetch(statusForm.action || window.location.href, {
                    method: 'POST',
                    body: formData,
                    headers: {'X-Requested-With': 'XMLHttpRequest'}
                })
                    .then(function(response){
                        if (!response.ok) {
                            throw new Error('Request failed');
                        }
                        return response.json();
                    })
                    .then(function(data){
                        status.innerHTML = buildStatusResult(data.row);
                        status.style.display = 'block';
                    })
                    .catch(function(){
                        status.innerHTML = '<h3>Tracking result</h3><p>Unable to check the request right now. Please try again.</p>';
                        status.style.display = 'block';
                    })
                    .finally(function(){
                        submitButton.disabled = false;
                        submitButton.textContent = 'Track';
                    });
            });
        }
    });

    function escapeHtml(value){
        return String(value ?? '').replace(/[&<>'"]/g, function(character){
            return {'&':'&amp;', '<':'&lt;', '>':'&gt;', "'":'&#039;', '"':'&quot;'}[character];
        });
    }

    function buildStatusResult(row){
        if (!row) {
            return '<h3>Tracking result</h3><p>No request found for this track number.</p>' + backButton();
        }

        let result = '<h3>Your request status</h3>'
            + '<p>TRACK NUMBER : ' + escapeHtml(row.track_no) + '</p>'
            + '<p>WASTE TYPE : ' + escapeHtml(row.waste_type) + '</p>'
            + '<p>WASTE STATUS : ' + escapeHtml(row.waste_status) + '</p>';

        if (row.waste_status === 'completed') {
            result += '<p>ACTUAL WEIGHT : ' + escapeHtml(row.actual_weight) + ' kg</p>'
                + '<p>CONDITION : ' + escapeHtml(row.waste_condition) + '</p>'
                + '<p>CONDITION MULTIPLIER : ' + Number(row.multiplier).toFixed(2) + '</p>'
                + '<p>AMOUNT TO BE PAID : ' + Number(row.payout).toFixed(2) + '</p>';

            if (row.receipt_id) {
                result += '<a href="trials_receipt.php?id=' + encodeURIComponent(row.receipt_id) + '"><button type="button">Download receipt</button></a>';
            }
        }

        return result + backButton();
    }

    function backButton(){
        return '<button type="button" onclick="document.getElementById(\'status\').style.display=\'none\';">back</button>';
    }

    var div6=document.getElementById("account");
    var display=0;

    function accounts(event){
        if(!div6) return;
        if(event){
            event.stopPropagation();
        }

        if(display==1){
            div6.style.display='none';
            display=0;
        }else{
            div6.style.display='flex';
            display=1;
        }
    }

    document.addEventListener('click', function(event){
        if (!div6) return;

        const clickedInsideMenu = event.target.closest('#account');
        const clickedProfile = event.target.closest('.icon2');

        if (!clickedInsideMenu && !clickedProfile) {
            div6.style.display='none';
            display=0;
        }
    });

    function copyText(){
        const trackingNumber=document.getElementById("trackNo").innerText;
        navigator.clipboard.writeText(trackingNumber);
    }
