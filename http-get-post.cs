using System;
using System.Collections.Generic;
using System.Net.Http;
using System.Security.Cryptography.X509Certificates;
using System.Threading.Tasks;


namespace SmsGwDemo
{
    class Program
    {
        static void Main(string[] args)
        {
            Task t = new Task(Ping);
            t.Start();
            Console.ReadLine();
        }

        static async void Ping()
        {
            string page = "https://www.ipsms.cz:8443/smsconnector/getpost/GP";

            var pageParams = new FormUrlEncodedContent(new[]
            {
                new KeyValuePair<string, string>("action", "ping"),
                new KeyValuePair<string, string>("baID", "demo")
            });

            var cert = new X509Certificate2("demo.pfx", "password");
            var clientHandler = new WebRequestHandler();
            clientHandler.ClientCertificates.Add(cert);

            using (HttpClient client = new HttpClient(clientHandler))
            using (HttpResponseMessage response = await client.PostAsync(page, pageParams))
            using (HttpContent content = response.Content)
            {
                string result = await content.ReadAsStringAsync();
                if (result != null)
                {
                    Console.WriteLine(result);
                }
            }
        }

    }
}
