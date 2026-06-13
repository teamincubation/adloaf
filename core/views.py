import json
from django.shortcuts import render
from django.http import JsonResponse
from django.views.decorators.http import require_http_methods
from .models import ContactMessage

def index_view(request):
    return render(request, 'core/index.html')

@require_http_methods(["POST"])
def contact_api_view(request):
    try:
        data = json.loads(request.body.decode('utf-8'))
        
        full_name = data.get('full_name', '').strip()
        email = data.get('email', '').strip()
        subject = data.get('subject', '').strip()
        service_type = data.get('service_type', '').strip()
        message = data.get('message', '').strip()
        
        if not (full_name and email and subject and service_type and message):
            return JsonResponse({'success': False, 'error': 'All fields are required.'}, status=400)
            
        ContactMessage.objects.create(
            full_name=full_name,
            email=email,
            subject=subject,
            service_type=service_type,
            message=message
        )
        
        return JsonResponse({'success': True})
    except json.JSONDecodeError:
        return JsonResponse({'success': False, 'error': 'Malformed request body.'}, status=400)
    except Exception as e:
        return JsonResponse({'success': False, 'error': str(e)}, status=500)
