<template>
  <Header />
  <div class="profile-page">
    <div class="container">
      <div class="profile-header">
        <h1>My Profile</h1>
      </div>

      <div v-if="isLoading" class="loading">
        <div class="spinner"></div>
        <p>Loading profile...</p>
      </div>

      <div v-else class="profile-content">
        <!-- Display Mode -->
        <div v-if="!isEditing" class="profile-card">
          <div class="profile-section">
            <div class="profile-avatar">
              <div class="avatar-circle" :class="{ 'has-image': user.imageUrl }">
                <img v-if="user.imageUrl" :src="user.imageUrl" alt="Profile" />
                <span v-else>{{ user.username?.[0]?.toUpperCase() || 'U' }}</span>
              </div>
            </div>
            
            <div class="profile-info">
              <div class="info-row">
                <label>Username</label>
                <p>{{ user.username }}</p>
              </div>
              
              <div class="info-row">
                <label>Email</label>
                <p>{{ user.email }}</p>
              </div>
              
              <div class="info-row">
                <label>First Name</label>
                <p>{{ user.firstName || 'Not set' }}</p>
              </div>
              
              <div class="info-row">
                <label>Last Name</label>
                <p>{{ user.lastName || 'Not set' }}</p>
              </div>
              
              <div class="info-row">
                <label>Phone Number</label>
                <p>{{ user.phoneNumber || 'Not set' }}</p>
              </div>
              
              <div class="info-row">
                <label>Member Since</label>
                <p>{{ formatDate(user.createdAt) }}</p>
              </div>
            </div>
          </div>
          
          <div class="profile-actions">
            <button @click="startEditing" class="btn btn-primary">
              Edit Profile
            </button>
          </div>
        </div>

        <!-- Edit Mode -->
        <div v-else class="profile-card edit-mode">
          <h2>Edit Profile</h2>
          
          <form @submit.prevent="saveProfile" class="edit-form">
            <!-- Profile Photo Upload -->
            <div class="form-group">
              <label>Profile Photo</label>
              <div class="photo-upload-section">
                <div class="photo-preview">
                  <div class="avatar-circle preview" :class="{ 'has-image': photoPreview || editForm.imageUrl }">
                    <img v-if="photoPreview || editForm.imageUrl" :src="photoPreview || editForm.imageUrl" alt="Profile preview" />
                    <span v-else>{{ editForm.username?.[0]?.toUpperCase() || 'U' }}</span>
                  </div>
                </div>
                <div class="photo-upload-controls">
                  <input
                    type="file"
                    ref="photoInput"
                    @change="handlePhotoSelect"
                    accept="image/*"
                    style="display: none"
                    :disabled="isSaving"
                  />
                  <button
                    type="button"
                    @click="$refs.photoInput.click()"
                    class="btn btn-secondary"
                    :disabled="isSaving"
                  >
                    Choose Photo
                  </button>
                  <button
                    v-if="editForm.imageUrl || photoPreview"
                    type="button"
                    @click="removePhoto"
                    class="btn btn-danger"
                    :disabled="isSaving"
                  >
                    Remove
                  </button>
                </div>
              </div>
            </div>
            
            <div class="form-group">
              <label for="username">Username</label>
              <input
                type="text"
                id="username"
                v-model="editForm.username"
                required
                :disabled="isSaving"
              />
            </div>
            
            <div class="form-group">
              <label for="email">Email</label>
              <input
                type="email"
                id="email"
                v-model="editForm.email"
                required
                :disabled="isSaving"
              />
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="firstName">First Name</label>
                <input
                  type="text"
                  id="firstName"
                  v-model="editForm.firstName"
                  :disabled="isSaving"
                />
              </div>
              
              <div class="form-group">
                <label for="lastName">Last Name</label>
                <input
                  type="text"
                  id="lastName"
                  v-model="editForm.lastName"
                  :disabled="isSaving"
                />
              </div>
            </div>
            
            <div class="form-group">
              <label for="phoneNumber">Phone Number</label>
              <input
                type="tel"
                id="phoneNumber"
                v-model="editForm.phoneNumber"
                :disabled="isSaving"
              />
            </div>
            
            <div class="form-actions">
              <button type="button" @click="cancelEditing" class="btn btn-secondary" :disabled="isSaving">
                Cancel
              </button>
              <button type="submit" class="btn btn-primary" :disabled="isSaving">
                {{ isSaving ? 'Saving...' : 'Save Changes' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Toast Notification -->
    <Transition name="toast">
      <div v-if="showNotification" class="toast-notification">
        {{ notificationMessage }}
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted, inject } from 'vue';
import { useRouter } from 'vue-router';
import axios from '../services/axiosConfig';
import Header from '../components/Header.vue';
import { isTokenExpired, clearAuthState } from '../services/authHelpers';

const router = useRouter();
const $auth = inject('$auth');
const user = ref({});
const editForm = ref({});
const isLoading = ref(true);
const isEditing = ref(false);
const isSaving = ref(false);
const showNotification = ref(false);
const notificationMessage = ref('');
const photoInput = ref(null);
const photoPreview = ref(null);
const selectedFile = ref(null);

onMounted(async () => {
  // Check token expiration immediately
  const token = localStorage.getItem('jwtToken');
  if (!token || isTokenExpired(token)) {
    clearAuthState($auth);
    router.push('/login');
    return;
  }
  await loadProfile();
});

async function loadProfile() {
  const storedUser = JSON.parse(localStorage.getItem('user'));
  if (!storedUser || !storedUser.userId) {
    router.push('/login');
    return;
  }

  try {
    isLoading.value = true;
    const response = await axios.get(`/users/${storedUser.userId}`);
    user.value = response.data;
  } catch (error) {
    console.error('Failed to load profile:', error);
    showToast('Failed to load profile');
  } finally {
    isLoading.value = false;
  }
}

function startEditing() {
  editForm.value = {
    username: user.value.username,
    email: user.value.email,
    firstName: user.value.firstName || '',
    lastName: user.value.lastName || '',
    phoneNumber: user.value.phoneNumber || '',
    imageUrl: user.value.imageUrl || null
  };
  photoPreview.value = null;
  selectedFile.value = null;
  isEditing.value = true;
}

function cancelEditing() {
  isEditing.value = false;
  editForm.value = {};
  photoPreview.value = null;
  selectedFile.value = null;
}

function handlePhotoSelect(event) {
  const file = event.target.files[0];
  if (!file) return;

  if (!file.type.startsWith('image/')) {
    showToast('Please select an image file');
    return;
  }

  if (file.size > 5 * 1024 * 1024) {
    showToast('Image size must be less than 5MB');
    return;
  }

  selectedFile.value = file;
  
  const reader = new FileReader();
  reader.onload = (e) => {
    photoPreview.value = e.target.result;
  };
  reader.readAsDataURL(file);
}

function removePhoto() {
  photoPreview.value = null;
  selectedFile.value = null;
  editForm.value.imageUrl = null;
  if (photoInput.value) {
    photoInput.value.value = '';
  }
}

async function saveProfile() {
  try {
    isSaving.value = true;
    
    let response;
    if (selectedFile.value) {
      // Upload with image
      const formData = new FormData();
      formData.append('firstName', editForm.value.firstName || '');
      formData.append('lastName', editForm.value.lastName || '');
      formData.append('username', editForm.value.username);
      formData.append('email', editForm.value.email);
      formData.append('phoneNumber', editForm.value.phoneNumber || '');
      formData.append('image', selectedFile.value);
      
      response = await axios.put(`/users/${user.value.userId}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
    } else if (editForm.value.imageUrl === null && user.value.imageUrl) {
      // User removed the image
      const formData = new FormData();
      formData.append('firstName', editForm.value.firstName || '');
      formData.append('lastName', editForm.value.lastName || '');
      formData.append('username', editForm.value.username);
      formData.append('email', editForm.value.email);
      formData.append('phoneNumber', editForm.value.phoneNumber || '');
      formData.append('removeImage', 'true');
      
      response = await axios.put(`/users/${user.value.userId}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
    } else {
      // Update without image changes
      response = await axios.put(`/users/${user.value.userId}`, editForm.value);
    }
    
    // Reload user data
    const userResponse = await axios.get(`/users/${user.value.userId}`);
    user.value = userResponse.data;
    
    // Update localStorage
    const storedUser = JSON.parse(localStorage.getItem('user'));
    localStorage.setItem('user', JSON.stringify({
      ...storedUser,
      username: user.value.username,
      email: user.value.email
    }));
    
    isEditing.value = false;
    photoPreview.value = null;
    selectedFile.value = null;
    showToast('Profile updated successfully!');
  } catch (error) {
    console.error('Failed to update profile:', error);
    showToast('Failed to update profile');
  } finally {
    isSaving.value = false;
  }
}

function showToast(message, duration = 3000) {
  notificationMessage.value = message;
  showNotification.value = true;
  setTimeout(() => {
    showNotification.value = false;
  }, duration);
}

function formatDate(dateString) {
  if (!dateString) return 'N/A';
  return dateString;
}
</script>

<style scoped>
.profile-page {
  padding-top: 70px;
  background: linear-gradient(135deg, #0f0f1e 0%, #1a1a2e 100%);
  min-height: 100vh;
  padding-bottom: 60px;
}

.container {
  max-width: 800px;
  margin: 0 auto;
  padding: 40px 30px;
}

.profile-header {
  margin-bottom: 40px;
}

.profile-header h1 {
  font-size: 2.5em;
  color: white;
  font-weight: 800;
  margin: 0;
}

.loading {
  text-align: center;
  padding: 80px 20px;
  color: white;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid rgba(233, 69, 96, 0.2);
  border-top-color: #e94560;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 20px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.profile-content {
  animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.profile-card {
  background: linear-gradient(135deg, #16213e 0%, #0f0f1e 100%);
  border-radius: 16px;
  padding: 40px;
  border: 2px solid rgba(233, 69, 96, 0.2);
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.profile-section {
  margin-bottom: 40px;
}

.profile-avatar {
  text-align: center;
  margin-bottom: 40px;
}

.avatar-circle {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  background: linear-gradient(135deg, #e94560 0%, #ff6b81 100%);
  color: white;
  font-size: 3em;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
  box-shadow: 0 10px 30px rgba(233, 69, 96, 0.4);
  overflow: hidden;
}

.avatar-circle.has-image {
  background: transparent;
}

.avatar-circle img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-circle.preview {
  width: 80px;
  height: 80px;
  font-size: 2em;
}

.photo-upload-section {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 15px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 8px;
}

.photo-preview {
  flex-shrink: 0;
}

.photo-upload-controls {
  display: flex;
  gap: 10px;
}

.btn-danger {
  background: transparent;
  border: 2px solid #dc3545;
  color: #dc3545;
}

.btn-danger:hover:not(:disabled) {
  background: #dc3545;
  color: white;
  transform: translateY(-2px);
}

.profile-info {
  display: grid;
  gap: 24px;
}

.info-row {
  display: grid;
  grid-template-columns: 150px 1fr;
  gap: 20px;
  align-items: baseline;
  padding-bottom: 24px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.info-row:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.info-row label {
  color: #b0b0b0;
  font-weight: 600;
  font-size: 0.9em;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-row p {
  color: white;
  font-size: 1.1em;
  margin: 0;
  font-weight: 500;
}

.profile-actions {
  display: flex;
  justify-content: center;
  padding-top: 20px;
}

.edit-mode h2 {
  color: white;
  font-size: 1.8em;
  margin: 0 0 30px 0;
  font-weight: 700;
}

.edit-form {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  color: #b0b0b0;
  font-weight: 600;
  font-size: 0.9em;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.form-group input {
  background: rgba(255, 255, 255, 0.05);
  border: 2px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 12px 16px;
  color: white;
  font-size: 1em;
  transition: all 0.3s ease;
}

.form-group input:focus {
  outline: none;
  border-color: #e94560;
  background: rgba(255, 255, 255, 0.08);
}

.form-group input:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 16px;
}

.btn {
  padding: 12px 32px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  border: none;
  font-size: 1em;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background: linear-gradient(135deg, #e94560 0%, #ff6b81 100%);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(233, 69, 96, 0.4);
}

.btn-secondary {
  background: transparent;
  border: 2px solid #6c757d;
  color: #6c757d;
}

.btn-secondary:hover:not(:disabled) {
  background: #6c757d;
  color: white;
  transform: translateY(-2px);
}

.toast-notification {
  position: fixed;
  bottom: 30px;
  right: 30px;
  background: linear-gradient(135deg, #e94560 0%, #ff6b81 100%);
  color: white;
  padding: 16px 24px;
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(233, 69, 96, 0.4);
  z-index: 10000;
  font-weight: 600;
}

.toast-enter-active, .toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  transform: translateX(400px);
  opacity: 0;
}

.toast-leave-to {
  transform: translateY(50px);
  opacity: 0;
}

@media (max-width: 1024px) {
  .container {
    padding: 30px 20px;
  }
  
  .profile-card {
    padding: 30px;
  }
}

@media (max-width: 768px) {
  .container {
    padding: 20px 15px;
  }
  
  .profile-card {
    padding: 24px;
  }
  
  .info-row {
    grid-template-columns: 1fr;
    gap: 8px;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .form-actions {
    flex-direction: column;
  }
  
  .form-actions .btn {
    width: 100%;
  }
  
  h2 {
    font-size: 1.5em;
  }
}

@media (max-width: 480px) {
  .container {
    padding: 15px 10px;
  }
  
  .profile-card {
    padding: 20px;
  }
  
  h2 {
    font-size: 1.3em;
  }
  
  .btn {
    padding: 10px 20px;
    font-size: 0.9em;
  }
  
  .toast-notification {
    bottom: 20px;
    right: 20px;
    left: 20px;
    padding: 12px 16px;
    font-size: 0.9em;
  }
}
</style>
