# Google Cloud Image Gallery

## Getting Started

Create a new Google Cloud project from the console.

Open a new Google Cloud Shell. All remaining commands are executed through Google Cloud Shell. Ensure the active project is the one just created.

First, create an environment variable with the same of your project ID:

```export PROJECT_ID=edingc-image-gallery```

Clone this repository to the Cloud Shell and enter the directory:

```
git clone https://github.com/edingc/gc-image-gallery.git
cd gc-image-gallery
```
## Deploy Google App Engine PHP API

Create a Google App Engine application in the `us-central` region:

```gcloud app create --region=us-central```

Creating an App Engine bucket will automatically create an associated Google Cloud Storage bucket. The first 5GB of storage is free when associated with an App Engine application.

Find the name of the target bucket:

```gsutil ls```

Copy the sample images (yum, food!) to the target bucket. Do not copy to the "staging" bucket.

```gsutil cp images/* gs://edingc-image-gallery.appspot.com/```

The images will be served from this bucket, so they need to be made publicly accessible:

```gsutil iam ch allUsers:objectViewer gs://edingc-image-gallery.appspot.com/```

The image API is served through a PHP application on Google App Engine. It is a modified version of Google's example application for interacting with Cloud Storage buckets.

Change directories to the code for the App Engine:

```cd app-engine/```

The App Engine configuration must be updated with the name of the bucket hosting the files. In the Cloud Shell editor, or your favorite command line editor, edit `app.yaml` to include the correct name of the App Engine Cloud Storage bucket:

```
env_variables:
  GOOGLE_STORAGE_BUCKET: edingc-image-gallery.appspot.com # Modify this to match App Engine storage bucket
```

To deploy the app, the Cloud Build API must be enabled for the project:

```gcloud services enable cloudbuild.googleapis.com```

Deploy the app to Google App Engine (You must be in the same directory as app.yaml):

```gcloud app deploy```

After a few minutes the app is deployed. Use ```gcloud app browse``` to get the app's URL and test it in a browser. A JSON response containing the names and URLs of the images in the Cloud Storage bucket should be returned:

```
[{"name":"brussels.jpg","url":"https://edingc-image-gallery.appspot.com.storage.googleapis.com/brussels.jpg"},{"name":"carne.jpg","url":"https://edingc-image-gallery.appspot.com.storage.googleapis.com/carne.jpg"},{"name":"carrots.jpg","url":"https://edingc-image-gallery.appspot.com.storage.googleapis.com/carrots.jpg"},{"name":"chicken.jpg","url":"https://edingc-image-gallery.appspot.com.storage.googleapis.com/chicken.jpg"},{"name":"chinese.jpg","url":"https://edingc-image-gallery.appspot.com.storage.googleapis.com/chinese.jpg"},{"name":"eggs.jpg","url":"https://edingc-image-gallery.appspot.com.storage.googleapis.com/eggs.jpg"},{"name":"shrimp.jpg","url":"https://edingc-image-gallery.appspot.com.storage.googleapis.com/shrimp.jpg"},{"name":"steak.jpg","url":"https://edingc-image-gallery.appspot.com.storage.googleapis.com/steak.jpg"},{"name":"steak2.jpg","url":"https://edingc-image-gallery.appspot.com.storage.googleapis.com/steak2.jpg"},{"name":"wings.jpg","url":"https://edingc-image-gallery.appspot.com.storage.googleapis.com/wings.jpg"}]
```
Make note of the App Engine URL. It will be needed for configuration of the API gateway.

## Deploy API Gateway

This project uses an unauthenticated API gateway to frontend the Google App Engine instance.

Deploy a new API Gateway. You will be prompted to enable the API Gateway service:

```gcloud api-gateway apis create image-gallery-api```

The API specification template must be modified to point to the Google App Engine instance. First, change the working directory to the location of the API template:

```cd ~/gc-image-gallery/api-gateway```

Modify the template using the Cloud Shell editor to point to the Google App Engine URL:

```
x-google-backend:
  address: https://edingc-image-gallery.uc.r.appspot.com/ # Modify this to match App Engine backend
```

The App Engine service account must be used to deploy the API configuration. This account can be found in the Google Cloud Console on the **IAM** screen:

```
gcloud api-gateway api-configs create image-gallery-config \
  --api=image-gallery-api --openapi-spec=openapi2-functions.yaml \
  --backend-auth-service-account=edingc-image-gallery@appspot.gserviceaccount.com
```

Once the configuration is created, deploy the configuration to an API Gateway:

```
gcloud api-gateway gateways create image-gallery-gateway \
  --api=image-gallery-api --api-config=image-gallery-config \
  --location=us-central1
```

"Describe" the gateway to discover the public URL ("defaultHostname"):

```
gcloud api-gateway gateways describe image-gallery-gateway \
  --location=us-central1
```

Visiting the URL with ```/images``` appended (e.g. https://image-gallery-gateway-17z2baze.uc.gateway.dev/images) should display the listing of files in the Cloud Storage bucket:

 ```
 [{"name":"brussels.jpg","url":"https://edingc-image-gallery.appspot.com.storage.googleapis.com/brussels.jpg"},{"name":"carne.jpg","url":"https://edingc-image-gallery.appspot.com.storage.googleapis.com/carne.jpg"},{"name":"carrots.jpg","url":"https://edingc-image-gallery.appspot.com.storage.googleapis.com/carrots.jpg"},{"name":"chicken.jpg","url":"https://edingc-image-gallery.appspot.com.storage.googleapis.com/chicken.jpg"},{"name":"chinese.jpg","url":"https://edingc-image-gallery.appspot.com.storage.googleapis.com/chinese.jpg"},{"name":"eggs.jpg","url":"https://edingc-image-gallery.appspot.com.storage.googleapis.com/eggs.jpg"},{"name":"shrimp.jpg","url":"https://edingc-image-gallery.appspot.com.storage.googleapis.com/shrimp.jpg"},{"name":"steak.jpg","url":"https://edingc-image-gallery.appspot.com.storage.googleapis.com/steak.jpg"},{"name":"steak2.jpg","url":"https://edingc-image-gallery.appspot.com.storage.googleapis.com/steak2.jpg"},{"name":"wings.jpg","url":"https://edingc-image-gallery.appspot.com.storage.googleapis.com/wings.jpg"}]
 ```

Make note of the API URL. It will be needed for configuration of the gallery webpage.

 ## Deploy Gallery Container Image to Container Registry

 The actual webpage displaying the images is an HTML page hosted on a customized nginx Docker image. The page calls the API URL to load the images through AJAX. Later, the Docker image will be made highly available through Kubernetes.

Change the working directory to the location of the Dockerfile and HTML:

 ```cd ~/gc-image-gallery/k8s```

Modify the webpage (line 112) to point to the API URL using the Cloud Shell editor (HTML file is in `html` subdirectory):

```
$(document).ready(function() {
 $.getJSON("https://image-gallery-gateway-17z2baze.uc.gateway.dev/images") // Modify this to match API Gateway URL
  .done(function(data) {
```

Build the custom nginx Docker image using the Dockerfile in the directory:

```docker build -t gcr.io/${PROJECT_ID}/image-gallery .```

Push the customized Docker image to the container registry:

```docker push gcr.io/${PROJECT_ID}/image-gallery```

## Deploy Container Image to Kubernetes

Set the compute zone to `us-central1-a`. This will also require the compute API to be enabled:

```gcloud config set compute/zone us-central1-a```

Enable the containers API:

```gcloud services enable container.googleapis.com```

Create a Kubernetes cluster for the image gallery application:

```gcloud container clusters create image-gallery-cluster```

Configure ```kubectl``` with the credentials for the newly-created cluster:

```gcloud container clusters get-credentials image-gallery-cluster --zone us-central1-a```

Deploy the customized Docker image and create three replicas:

```
kubectl create deployment image-gallery --image=gcr.io/${PROJECT_ID}/image-gallery
kubectl scale deployment image-gallery --replicas=3
```

`kubectl get pods` displays the running replicas:

```
NAME                            READY   STATUS    RESTARTS   AGE
image-gallery-ff9688f98-hfd4p   1/1     Running   0          70s
image-gallery-ff9688f98-kqzhf   1/1     Running   0          70s
image-gallery-ff9688f98-tgk5t   1/1     Running   0          90s
```

To access the containers from the Internet, they must be configured behind a load balancer service:

```kubectl expose deployment image-gallery --name=image-gallery-service --type=LoadBalancer --port 80 --target-port 80```

After a few minutes, the external IP of the cluster can be obtained by running `kubectl get service`:

```
NAME                    TYPE           CLUSTER-IP     EXTERNAL-IP       PORT(S)        AGE
image-gallery-service   LoadBalancer   10.3.246.116   104.197.137.136   80:31510/TCP   3m24s
```

Visiting the IP address will display the image gallery. (Be patient, the initial load can take a few seconds!)

## Cleaning Up

The following commands can be used to remove things created by this tutorial:

```
gcloud container clusters delete image-gallery-cluster --zone us-central1-a
gcloud container images delete  gcr.io/${PROJECT_ID}/image-gallery
gcloud api-gateway gateways delete image-gallery-gateway --location=us-central1
gcloud api-gateway api-configs delete image-gallery-config --api=image-gallery-api
```

Endpoints must be deleted as well:

```
gcloud endpoints services list
gcloud endpoints services delete image-gallery-api-15k9sripxs7kq.apigateway.edingc-image-gallery.cloud.goog
```

Finally, delete the project:

```gcloud projects delete ${PROJECT_ID}```
