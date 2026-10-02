import biostarPython as g
from biostarPython import connect_pb2

# --- تنظیمات ---
GATEWAY_IP = '127.0.0.1'  # آدرس سرور G-SDK (اگر روی همون سیستم اجرا می‌کنید)
GATEWAY_PORT = 4000       # پورت پیش‌فرض سرور G-SDK
DEVICE_IP = '192.168.0.110' # آدرس IP دستگاه BioLite Net خودتون
DEVICE_PORT = 51211       # پورت پیش‌فرض دستگاه (معمولاً 51211)
# ----------------

try:
    # ۱. اتصال به Gateway
    client = g.GatewayClient(GATEWAY_IP, GATEWAY_PORT)
    channel = client.getChannel()
    print("✅ اتصال به Gateway برقرار شد.")

    # ۲. اتصال به دستگاه
    connectSvc = g.ConnectSvc(channel)
    connInfo = connect_pb2.ConnectInfo(IPAddr=DEVICE_IP, port=DEVICE_PORT, useSSL=False)
    deviceID = connectSvc.connect(connInfo)
    print(f"✅ اتصال به دستگاه با شناسه {deviceID} برقرار شد.")

    # ۳. دریافت اطلاعات دستگاه (مثلاً مدل و نسخه فرم‌ور)
    deviceInfo = connectSvc.getInfo(deviceID)
    print(f"📋 اطلاعات دستگاه: {deviceInfo}")

except Exception as e:
    print(f"❌ خطا: {e}")