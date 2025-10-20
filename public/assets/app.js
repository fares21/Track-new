(function(){
  const timeline = document.getElementById('timeline');
  if (!timeline) return;
  const num = timeline.getAttribute('data-number');
  const poll = () => {
    fetch(`/track.php?num=${encodeURIComponent(num)}&ajax=1`, {cache:'no-store'})
      .then(r=>r.json()).then(j=>{
        if (!j.ok) return;
        // إعادة بناء الخط الزمني
        const html = j.events.length ? `<ul class="v-timeline">${
          j.events.map(ev=>`
            <li>
              <div class="dot"></div>
              <div class="content">
                <div class="row">
                  <span class="time">${ev.event_time.slice(0,16)}</span>
                  <span class="status">${ev.status_text}</span>
                </div>
                <div class="location">${ev.location||''}</div>
              </div>
            </li>
          `).join('')
        }</ul>` : `<div class="empty">No events yet.</div>`;
        timeline.innerHTML = html;
      }).catch(()=>{});
  };
  setInterval(poll, 60000); // كل 60 ثانية
})();
