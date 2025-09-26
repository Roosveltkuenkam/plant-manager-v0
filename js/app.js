// ---- Fonction générique pour appeler l'API ----
const api = (action, data = {}) => {
  const form = new FormData();
  form.append('action', action);
  for (const k in data) {
    form.append(k, data[k]);
  }
  return fetch('api/api.php', {
    method: 'POST',
    body: form
  }).then(r => r.json());
};

// ---- Upload d'image ----
async function uploadImage(file) {
  const fm = new FormData();
  fm.append('image', file);
  const res = await fetch('api/upload.php', {
    method: 'POST',
    body: fm
  });
  return res.json();
}

// ---- Gestion utilisateur ----
async function apiMe() {
  return await api('me');
}
async function showUserBar(user) {
  const bar = document.getElementById('user-bar');
  if (!user) { bar.style.display = 'none'; return; }
  bar.style.display = 'flex';
  bar.innerHTML = `<img src="${user.photo||'uploads/default.png'}" style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid #a5d6a7;"> <b>${user.name||user.email}</b>`;
}
function showTab(tab) {
  document.querySelectorAll('.tab-section').forEach(sec => sec.style.display = 'none');
  document.getElementById('tab-' + tab).style.display = '';
  document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
  document.querySelector('.tab-btn[data-tab="' + tab + '"]').classList.add('active');
}
async function checkAuth() {
  const {user} = await apiMe();
  if (!user) {
    showTab('login');
    showUserBar(null);
    document.querySelector('.tabs').style.display = 'none';
    document.querySelectorAll('.tab-section').forEach(sec => {
      if (!['tab-login','tab-register'].includes(sec.id)) sec.style.display = 'none';
    });
  } else {
    showUserBar(user);
    document.querySelector('.tabs').style.display = '';
    showTab('list');
    loadPlants();
  }
}
document.addEventListener('DOMContentLoaded', () => {
  checkAuth();
  // Navigation login/register
  document.getElementById('show-register').onclick = e => { e.preventDefault(); showTab('register'); };
  document.getElementById('show-login').onclick = e => { e.preventDefault(); showTab('login'); };
  // Connexion
  document.getElementById('loginForm').onsubmit = async e => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const res = await api('login', Object.fromEntries(fd));
    if (res.ok) checkAuth();
    else alert(res.error||'Erreur');
  };
  // Inscription
  document.getElementById('registerForm').onsubmit = async e => {
    e.preventDefault();
    const fd = new FormData(e.target);
    let photoPath = '';
    if (fd.get('photo') && fd.get('photo').name) {
      const up = await uploadImage(fd.get('photo'));
      if (up.ok) photoPath = up.path;
    }
    const res = await api('register', {
      name: fd.get('name'),
      email: fd.get('email'),
      password: fd.get('password'),
      photo: photoPath
    });
    if (res.ok) checkAuth();
    else alert(res.error||'Erreur');
  };
  // Déconnexion
  document.getElementById('logoutBtn').onclick = async () => {
    await api('logout');
    checkAuth();
  };
  // Affichage profil
  document.querySelector('.tab-btn[data-tab="profile"]').onclick = async () => {
    showTab('profile');
    const {user} = await apiMe();
    if (user) {
      document.querySelector('#profileForm [name=name]').value = user.name||'';
      document.getElementById('profile-photo').innerHTML = `<img src="${user.photo||'uploads/default.png'}" style="width:70px;height:70px;border-radius:50%;object-fit:cover;border:2px solid #a5d6a7;">`;
    }
  };
  // Modifier profil
  document.getElementById('profileForm').onsubmit = async e => {
    e.preventDefault();
    const fd = new FormData(e.target);
    let photoPath = '';
    if (fd.get('photo') && fd.get('photo').name) {
      const up = await uploadImage(fd.get('photo'));
      if (up.ok) photoPath = up.path;
    }
    const res = await api('update_profile', {
      name: fd.get('name'),
      password: fd.get('password'),
      photo: photoPath
    });
    if (res.ok) {
      alert('Profil mis à jour');
      checkAuth();
    } else alert(res.error||'Erreur');
  };

  // Onglets
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', e => {
      showTab(btn.dataset.tab);
      if (btn.dataset.tab === 'list') loadPlants();
      if (btn.dataset.tab === 'history') showHistoryContent();
    });
  });
  showTab('add');

  // ---- Charger et afficher toutes les plantes ----
  async function loadPlants() {
    const json = await api('list_plants');
    const container = document.getElementById('plants');
    container.innerHTML = '';

    if (!json.ok) {
      container.innerHTML = '<i>Erreur lors du chargement des plantes</i>';
      return;
    }

    if (!json.plants.length) {
      container.innerHTML = '<i>Aucune plante pour l’instant...</i>';
      return;
    }

    json.plants.forEach(p => {
      const div = document.createElement('div');
      div.className = 'card';

      const img = document.createElement('img');
      img.src = (p.image_path && p.image_path !== 'null' && p.image_path !== '') ? p.image_path : 'uploads/default.png';
      img.alt = p.name;
      img.width = 150;
      // Signal visuel si la plante doit être arrosée
      if (p.due) {
        const badge = document.createElement('span');
        badge.textContent = '💧 À arroser';
        badge.style = 'position:absolute;top:10px;right:10px;background:#ff5252;color:#fff;padding:3px 10px;border-radius:12px;font-size:0.95em;font-weight:700;box-shadow:0 2px 8px #ff525299;z-index:2;';
        div.appendChild(badge);
      }

      const h3 = document.createElement('h3');
      h3.textContent = p.name;

      const sp = document.createElement('p');
      sp.textContent = `Espèce : ${p.species || '-'}`;

      const water = document.createElement('p');
      water.textContent = `Arrosage : ${p.water_amount || 0} L / ${p.water_interval_days || 0} jours`;

      // Actions
      const btns = document.createElement('div');
      btns.style.marginTop = '8px';

      // Bouton arroser
      const waterBtn = document.createElement('button');
      waterBtn.textContent = '💧 Arroser';
      waterBtn.onclick = async () => {
        const amount = prompt('Quantité d’eau utilisée (optionnel) :', p.water_amount || '');
        await api('water_plant', { plant_id: p.id, amount });
        alert('Arrosage enregistré !');
        loadPlants();
      };

      // Bouton historique
      const histBtn = document.createElement('button');
      histBtn.textContent = '🕑 Historique';
      histBtn.onclick = () => {
        showTab('history');
        loadHistory(p.id, p.name);
      };

      // Bouton supprimer
      const delBtn = document.createElement('button');
      delBtn.textContent = '🗑️ Supprimer';
      delBtn.onclick = async () => {
        if (confirm('Supprimer cette plante ?')) {
          await api('delete_plant', { plant_id: p.id });
          loadPlants();
        }
      };

      btns.appendChild(waterBtn);
      btns.appendChild(histBtn);
      btns.appendChild(delBtn);
      div.appendChild(img);
      div.appendChild(h3);
      div.appendChild(sp);
      div.appendChild(water);
      div.appendChild(btns);
      container.appendChild(div);
    });
  }

  // ---- Affichage de l’historique d’arrosage ----
  async function loadHistory(plant_id, plant_name) {
    const json = await api('watering_history', { plant_id });
    const container = document.getElementById('history-content');
    container.innerHTML = `<h3>Historique pour <b>${plant_name}</b></h3>`;
    if (!json.ok || !json.history.length) {
      container.innerHTML += '<i>Aucun arrosage enregistré.</i>';
      return;
    }
    const ul = document.createElement('ul');
    json.history.forEach(h => {
      const li = document.createElement('li');
      li.textContent = `${h.watered_at} : ${h.amount || '?'} L ${h.note ? ' - ' + h.note : ''}`;
      ul.appendChild(li);
    });
    container.appendChild(ul);
  }
  function showHistoryContent() {
    document.getElementById('history-content').innerHTML = '<p><i>Sélectionnez une plante pour voir l’historique.</i></p>';
  }

  const form = document.getElementById('plantForm');
  if (form) {
    form.addEventListener('submit', handleAddPlant);
  }
});

// ---- Gestion du formulaire d'ajout de plante ----
async function handleAddPlant(e) {
  e.preventDefault();

  const name = document.getElementById('plant_name').value;
  const species = document.getElementById('plant_species').value;
  const purchase_date = document.getElementById('plant_date').value;
  const water_amount = document.getElementById('plant_water_amount').value;
  const water_interval_days = document.getElementById('plant_water_interval').value;
  const imageFile = document.getElementById('plant_image').files[0];

  let image_path = '';
  if (imageFile) {
    const up = await uploadImage(imageFile);
    if (up.ok) {
      image_path = up.path;
    }
  }

  const res = await api('add_plant', {
    name,
    species,
    purchase_date,
    image_path,
    water_amount,
    water_interval_days
  });

  if (res.ok) {
    alert('Plante ajoutée avec succès ✅');
    document.getElementById('plantForm').reset();
    loadPlants();
  } else {
    alert('Erreur : ' + (res.error || 'Impossible d’ajouter la plante'));
  }
}
