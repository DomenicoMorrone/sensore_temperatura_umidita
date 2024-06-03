#include <WiFi.h>
#include <HTTPClient.h>
#include <ESP_Mail_Client.h>
#include <DHT.h> 
#include <driver/ledc.h> // Include la libreria per il controllo LEDC


#define DHTPIN 16 //D16 
#define DHTTYPE DHT11 
#define SMTP_HOST "smtp.gmail.com"
#define SMTP_PORT esp_mail_smtp_port_587

#define AUTHOR_EMAIL "morrone.domenico97@gmail.com"
#define AUTHOR_PASSWORD "cwme abhs weoo wefa"
#define RECIPIENT_EMAIL "morrone097@gmail.com"
#define LEDC_CHANNEL 0 // Canale LEDC
#define LEDC_TIMER 0   // Timer LEDC
#define LEDC_FREQUENCY 1000 // Frequenza PWM (1 kHz)
#define BUZZER_PIN 26 // Pin del buzzer passivo

SMTPSession smtp;

void smtpCallback(SMTP_Status status);

DHT dht11(DHTPIN, DHTTYPE); 

String URL = "http://192.168.43.151/progetto_sensore/gestione.php";

const char* ssid = "HUAWEI P40 lite"; 
const char* password = "Morrone1997"; 

int temperatura = 0;
int umidita = 0;
String sensore = "ESP32";
String nomestanza= "Salone";
bool buzzerAttivo = false;
unsigned long tempoInizioAllarme = 0; // Variabile per memorizzare il tempo di inizio dell'allarme
unsigned long previousMillis = millis()-300000; // Variabile per memorizzare l'ultimo tempo di esecuzione
const long interval = 300000; // Intervallo di 5 minuti in millisecondi

void setup() {
  Serial.begin(115200);
  dht11.begin(); 
  pinMode(BUZZER_PIN, OUTPUT); // Imposta il pin del buzzer come uscita
  // Configura il timer LEDC
  ledcSetup(LEDC_CHANNEL, LEDC_FREQUENCY, 8); // Canale, frequenza, risoluzione (8 bit)
  // Associa il canale LEDC al pin del buzzer
  ledcAttachPin(BUZZER_PIN, LEDC_CHANNEL);
  connectWiFi();
}

void loop() {
  unsigned long currentMillis = millis();

  if(WiFi.status() != WL_CONNECTED) {
    connectWiFi();
  }

  // Esegui le operazioni solo se è trascorso l'intervallo
  if (currentMillis - previousMillis >= interval) {
    previousMillis = currentMillis;
    
    Load_DHT11_Data();

    String postData = "sensoreId="+String(sensore) + "&stanza="+ String(nomestanza) + "&temperatura=" + String(temperatura) + "&umidita=" + String(umidita);

    

    if(temperatura < 10 || temperatura > 20 || umidita < 30 || umidita > 60) {
      
      if (!buzzerAttivo) {
        allarme(); // Attiva il buzzer solo se non è già attivo
      }

      MailClient.clearAP();
      MailClient.addAP(ssid, password);
      smtp.debug(1);
      smtp.callback(smtpCallback);

      Session_Config config;
      config.server.host_name = SMTP_HOST;
      config.server.port = SMTP_PORT;
      config.login.email = AUTHOR_EMAIL;
      config.login.password = AUTHOR_PASSWORD;
      config.login.user_domain = F("");
      config.time.ntp_server = F("pool.ntp.org,time.nist.gov");
      config.time.gmt_offset = 3;
      config.time.day_light_offset = 0;

      SMTP_Message message;
      message.sender.name = F("esp32");
      message.sender.email = AUTHOR_EMAIL;

      String subject;
      String textMsg;

      if(temperatura < 10 || temperatura > 20) {
        subject += "VALORE TEMPERATURA ALTERATA ";
        textMsg += "La temperatura risulta essere al di fuori del regime ottimale, infatti il valore è: " + String(temperatura) + " °C \n";
      }

      if(umidita < 30 || umidita > 60) {
        subject += "VALORE UMIDITÀ ALTERATA ";
        textMsg += "L'umidità risulta essere al di fuori del regime ottimale, infatti il valore è: " + String(umidita) + " % \n";
      }

      message.subject = subject;
      message.addRecipient(F("Domenico"), RECIPIENT_EMAIL);
      message.text.content = textMsg;
      message.text.transfer_encoding = "base64";
      message.text.charSet = F("utf-8");
      message.priority = esp_mail_smtp_priority::esp_mail_smtp_priority_high;
      smtp.setTCPTimeout(10);

      if (!smtp.connect(&config)) {
        MailClient.printf("Errore di connessione, Status Code: %d, Error Code: %d, Reason: %s\n", smtp.statusCode(), smtp.errorCode(), smtp.errorReason().c_str());
        return;
      }

      if (!smtp.isLoggedIn()) {
        Serial.println("Not yet logged in.");
      } else {
        if (smtp.isAuthenticated())
          Serial.println("Successfully logged in.");
        else
          Serial.println("Connected with no Auth.");
      }

      if (!MailClient.sendMail(&smtp, &message))
        MailClient.printf("Errore, Status Code: %d, Error Code: %d, Reason: %s\n", smtp.statusCode(), smtp.errorCode(), smtp.errorReason().c_str());
    } else {
      if (buzzerAttivo) {
        ledcWriteTone(LEDC_CHANNEL, 0); // Disattiva il buzzer
        buzzerAttivo = false; // Imposta lo stato del buzzer come disattivo
      }
    }

    HTTPClient http;
    http.begin(URL);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");
    
    int httpCode = http.POST(postData);
    String payload = "";

    if(httpCode > 0) {
      if(httpCode == HTTP_CODE_OK) {
        String payload = http.getString();
        Serial.println(payload);
      } else {
        Serial.printf("[HTTP] GET... code: %d\n", httpCode);
      }
    } else {
      Serial.printf("[HTTP] GET... failed, error: %s\n", http.errorToString(httpCode).c_str());
    }
    
    http.end();  //Close connection

    Serial.print("URL : "); Serial.println(URL); 
    Serial.print("Data: "); Serial.println(postData);
    Serial.print("httpCode: "); Serial.println(httpCode);
    Serial.print("payload : "); Serial.println(payload);
    Serial.println("--------------------------------------------------");
  }
  // Controlla se è passato un minuto dall'attivazione del buzzer
    if (buzzerAttivo && (millis() - tempoInizioAllarme >= 60000)) {
      ledcWriteTone(LEDC_CHANNEL, 0); // Disattiva il buzzer
      buzzerAttivo = false; // Imposta lo stato del buzzer come disattivo
    }
}

void allarme(){
  ledcWriteTone(LEDC_CHANNEL, 1000); // Attiva il buzzer con una frequenza di 1000 Hz
  buzzerAttivo = true; // Imposta lo stato del buzzer come attivo
  tempoInizioAllarme = millis(); // Registra il tempo di inizio dell'allarme 
}

void Load_DHT11_Data() {
  temperatura = dht11.readTemperature(); // Celsius
  umidita = dht11.readHumidity();
  
  if (isnan(temperatura) || isnan(umidita)) {
    Serial.println("Errore di lettura dal sensore!");
    temperatura = 0;
    umidita = 0;
  }
  Serial.printf("Temperatura: %d °C\n", temperatura);
  Serial.printf("Umidità: %d %%\n", umidita);
}

void connectWiFi() {
  WiFi.mode(WIFI_OFF);
  delay(1000);
  WiFi.mode(WIFI_STA);
  
  WiFi.begin(ssid, password);
  Serial.println("Connecting to WiFi");
  
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
    
  Serial.print("connected to : "); Serial.println(ssid);
  Serial.print("IP address: "); Serial.println(WiFi.localIP());
}

void smtpCallback(SMTP_Status status) {
  Serial.println(status.info());

  if (status.success()) {
    Serial.println("----------------");
    MailClient.printf("Messagio mandato con successo: %d\n", status.completedCount());
    MailClient.printf("Invio messaggio fallito: %d\n", status.failedCount());
    Serial.println("----------------\n");

    for (size_t i = 0; i < smtp.sendingResult.size(); i++) {
      SMTP_Result result = smtp.sendingResult.getItem(i);

      MailClient.printf("Message No: %d\n", i + 1);
      MailClient.printf("Status: %s\n", result.completed ? "success" : "failed");
      MailClient.printf("Date/Time: %s\n", MailClient.Time.getDateTimeString(result.timestamp, "%B %d, %Y %H:%M:%S").c_str());
      MailClient.printf("Recipient: %s\n", result.recipients.c_str());
      MailClient.printf("Subject: %s\n", result.subject.c_str());
    }
    Serial.println("----------------\n");

    smtp.sendingResult.clear();
  }
}


