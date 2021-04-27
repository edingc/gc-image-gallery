# Google Cloud Image Gallery

Create a new Google Cloud project from the console.

Open a new Google Cloud Shell. All reamining commands executured through a Google Cloud Shell.

First, create an environment variable with the same of your project ID:

```export PROJECT_ID=edingc-image-gallery```

Create a new Google Cloud project and set it as the active project in Cloud Shell.

```
gcloud projects create ${PROJECT_ID}
gcloud config set project ${PROJECT_ID}
```

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

The image API is served through a PHP API on Google App Engine. It is a modified version of Google's example application for interacting with Cloud Storage buckets.

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
