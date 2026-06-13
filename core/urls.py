from django.urls import path
from .views import index_view, contact_api_view

app_name = 'core'

urlpatterns = [
    path('', index_view, name='index'),
    path('api/contact/', contact_api_view, name='contact_api'),
]
