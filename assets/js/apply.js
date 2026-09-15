// apply.js - Apply wizard modal (Details -> Test -> Result -> Apply)
(function () {
  'use strict';

  const modalEl = document.getElementById('applyModal');
  if (!modalEl) return;

  const modal = new bootstrap.Modal(modalEl);

  const els = {
    title: document.getElementById('applyTitle'),
    sub: document.getElementById('applySub'),
    step1: document.getElementById('step1'),
    step2: document.getElementById('step2'),
    step3: document.getElementById('step3'),
    step4: document.getElementById('step4'),
    nextBtn: document.getElementById('nextBtn'),
    applyNowBtn: document.getElementById('applyNowBtn'),
    finalApplyBtn: document.getElementById('finalApplyBtn'),
    prevBtn: document.getElementById('prevBtn'),
    startTestBtn: document.getElementById('startTestBtn'),
    testReady: document.getElementById('testReady'),
    noTestReady: document.getElementById('noTestReady'),
    testTitle: document.getElementById('testTitle'),
    testDur: document.getElementById('testDur'),
    testInfo: document.getElementById('testInfo'),
    timerBox: document.getElementById('timerBox'),
    questionsWrap: document.getElementById('questionsWrap'),
    submitTestBtn: document.getElementById('submitTestBtn'),
    apPhone: document.getElementById('ap-phone'),
    apCity: document.getElementById('ap-city'),
    apLinkedin: document.getElementById('ap-linkedin'),
    apGithub: document.getElementById('ap-github'),
    apBio: document.getElementById('ap-bio'),
    resCorrect: document.getElementById('resCorrect'),
    resWrong: document.getElementById('resWrong'),
    resSkipped: document.getElementById('resSkipped'),
    resTotal: document.getElementById('resTotal'),
  };

  let current = null; // { id, title, company, type }
  let test = null;    // { duration_minutes, total, questions }
  let answers = {};   // idx -> selected option index
  let timer = null;
  let secondsLeft = 0;

  // ---------- wizard step switching ----------
  let step = 1;
  function refreshButtons() {
    els.nextBtn.classList.toggle('d-none', step !== 1);
    els.applyNowBtn.classList.toggle('d-none', !(step === 2 && test === null));
    els.finalApplyBtn.classList.toggle('d-none', step !== 4);
  }
  function setStep(n) {
    step = n;
    [els.step1, els.step2, els.step3, els.step4].forEach((s, i) => s.classList.toggle('d-none', i !== n - 1));
    document.querySelectorAll('.apply-ss').forEach(dot => {
      dot.classList.toggle('active', parseInt(dot.dataset.s, 10) <= n);
    });
    refreshButtons();
  }

  // ---------- load test for current internship ----------
  function loadTest(id) {
    els.testReady.classList.add('d-none');
    els.noTestReady.classList.add('d-none');
    return fetch('test_data.php?id=' + encodeURIComponent(id))
      .then(r => r.json())
      .then(data => {
        if (data && data.test) {
          test = data;
          els.testTitle.textContent = data.title || 'Screening Test';
          const mins = data.duration_minutes;
          els.testDur.textContent = mins === 1 ? '1 minute' : mins + ' minutes';
          els.testInfo.textContent = data.total + ' questions · ' + mins + ' minutes · unanswered questions are marked as skipped.';
          els.testReady.classList.remove('d-none');
          refreshButtons();
        } else {
          test = null;
          els.noTestReady.classList.remove('d-none');
          refreshButtons();
        }
      })
      .catch(() => {
        test = null;
        els.noTestReady.classList.remove('d-none');
        refreshButtons();
      });
  }

  // ---------- render questions ----------
  function renderQuestions() {
    els.questionsWrap.innerHTML = '';
    test.questions.forEach(q => {
      const card = document.createElement('div');
      card.className = 'card mb-3 border-0 shadow-sm';
      card.innerHTML =
        '<div class="card-body">' +
          '<div class="d-flex align-items-start gap-2 mb-2">' +
            '<span class="badge bg-navy rounded-pill mt-1">' + (q.i + 1) + '</span>' +
            '<div class="fw-semibold small lh-sm" style="white-space:pre-wrap">' + escapeHtml(q.q) + '</div>' +
          '</div>' +
          '<div class="d-grid gap-2 ps-1">' +
            q.o.map((opt, oi) =>
              '<input type="radio" name="tq-' + q.i + '" value="' + oi + '" class="btn-check" id="tq' + q.i + '_' + oi + '" autocomplete="off">' +
              '<label class="btn btn-outline-secondary w-100 text-start px-3 py-2" for="tq' + q.i + '_' + oi + '">' + escapeHtml(opt) + '</label>'
            ).join('') +
          '</div>' +
        '</div>';
      els.questionsWrap.appendChild(card);
    });
  }

  function escapeHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function collectAnswers() {
    const map = {};
    test.questions.forEach(q => {
      const sel = document.querySelector('input[name="tq-' + q.i + '"]:checked');
      if (sel) map[q.i] = parseInt(sel.value, 10);
    });
    return map;
  }

  // ---------- timer ----------
  function secondsToText(s) {
    const m = Math.floor(s / 60), sec = s % 60;
    return String(m).padStart(2, '0') + ':' + String(sec).padStart(2, '0');
  }
  function startTimer() {
    if (timer) clearInterval(timer);
    secondsLeft = (test.duration_minutes || 10) * 60;
    els.timerBox.textContent = secondsToText(secondsLeft);
    timer = setInterval(() => {
      secondsLeft--;
      els.timerBox.textContent = secondsToText(Math.max(0, secondsLeft));
      if (secondsLeft <= 0) {
        clearInterval(timer);
        submitTest();
      }
    }, 1000);
  }

  // ---------- submit test -> result ----------
  function submitTest() {
    if (timer) { clearInterval(timer); timer = null; }
    answers = collectAnswers();
    const answered = Object.keys(answers).length;
    const total = test.questions.length;
    const skipped = total - answered;

    els.resCorrect.textContent = answered;
    els.resCorrect.parentElement.querySelector('.lbl').textContent = 'Answered';
    els.resWrong.textContent = skipped;
    els.resWrong.parentElement.querySelector('.lbl').textContent = 'Skipped';
    els.resSkipped.parentElement.style.display = 'none';
    els.resTotal.textContent = total;

    setStep(4);
  }

  // ---------- apply submission ----------
  function submitApplication(testTaken) {
    const form = document.getElementById('applyFormHidden') || buildHiddenForm();
    form.internship_id.value = current.id;
    form.phone.value = els.apPhone.value.trim();
    form.city.value = els.apCity.value.trim();
    form.linkedin.value = els.apLinkedin.value.trim();
    form.github.value = els.apGithub.value.trim();
    form.bio.value = els.apBio.value.trim();
    form.test_taken.value = testTaken ? '1' : '0';
    form.test_answers.value = testTaken ? JSON.stringify(answers) : '';
    form.submit();
  }

  function buildHiddenForm() {
    const form = document.createElement('form');
    form.id = 'applyFormHidden';
    form.method = 'POST';
    form.action = 'internships.php';
    ['internship_id', 'phone', 'city', 'linkedin', 'github', 'bio', 'test_taken', 'test_answers'].forEach(k => {
      const i = document.createElement('input');
      i.type = 'hidden';
      i.name = k;
      form.appendChild(i);
    });
    const a = document.createElement('input');
    a.type = 'hidden';
    a.name = 'action';
    a.value = 'apply';
    form.appendChild(a);
    document.body.appendChild(form);
    return form;
  }

  // ---------- open / close ----------
  modalEl.addEventListener('show.bs.modal', function () {
    setStep(1);
    els.resSkipped.parentElement.style.display = '';
    els.resWrong.parentElement.querySelector('.lbl').textContent = 'Wrong';
    els.resCorrect.parentElement.querySelector('.lbl').textContent = 'Correct';
    els.resCorrect.textContent = '0';
    els.resWrong.textContent = '0';
    els.resSkipped.textContent = '0';
    els.resTotal.textContent = '0';
    loadTest(current.id);
  });

  modalEl.addEventListener('hide.bs.modal', function () {
    if (timer) { clearInterval(timer); timer = null; }
    test = null;
    step = 1;
  });

  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-apply');
    if (btn) {
      current = {
        id: btn.dataset.id,
        title: btn.dataset.title,
        company: btn.dataset.company,
        type: btn.dataset.type || 'Posting',
      };
      answers = {};
      if (timer) { clearInterval(timer); timer = null; }
      els.title.textContent = btn.dataset.title;
      els.sub.textContent = btn.dataset.company + ' · ' + btn.dataset.type;
      modal.show();
    }
  });

  // footer buttons
  els.nextBtn.addEventListener('click', () => setStep(2));
  els.applyNowBtn.addEventListener('click', () => submitApplication(false));
  els.finalApplyBtn.addEventListener('click', () => submitApplication(true));
  els.startTestBtn.addEventListener('click', () => {
    renderQuestions();
    setStep(3);
    startTimer();
  });
  els.submitTestBtn.addEventListener('click', submitTest);

  // auto-open via ?apply=ID
  const autoIdEl = document.getElementById('autoOpenId');
  if (autoIdEl && autoIdEl.value) {
    const btn = document.querySelector('.btn-apply[data-id="' + autoIdEl.value + '"]');
    if (btn) btn.click();
  }
})();