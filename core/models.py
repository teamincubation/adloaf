from django.db import models

class ContactMessage(models.Model):
    full_name = models.CharField(max_length=150)
    email = models.EmailField()
    subject = models.CharField(max_length=200)
    service_type = models.CharField(max_length=50)
    message = models.TextField()
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"{self.full_name} - {self.subject} ({self.created_at.strftime('%Y-%m-%d %H:%M')})"
