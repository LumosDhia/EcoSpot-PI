from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from deepface import DeepFace
import numpy as np
import base64
import cv2
import json
import os
import tempfile

app = FastAPI()

# Allow CORS for Symfony frontend
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"], # Allow all for easier testing
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

DB_PATH = "faces_db.json"

def load_db():
    if not os.path.exists(DB_PATH):
        return {}
    with open(DB_PATH, "r") as f:
        return json.load(f)

def save_db(db):
    with open(DB_PATH, "w") as f:
        json.dump(db, f)

class EnrollRequest(BaseModel):
    image: str
    user_id: str

class RecognizeRequest(BaseModel):
    image: str

def base64_to_tempfile(base64_string):
    try:
        if "," in base64_string:
            base64_string = base64_string.split(",")[1]
        img_data = base64.b64decode(base64_string)
        
        # DeepFace usually takes a path or a numpy array
        # Creating a temporary file is often most reliable
        fd, path = tempfile.mkstemp(suffix=".jpg")
        with os.fdopen(fd, 'wb') as tmp:
            tmp.write(img_data)
        return path
    except Exception as e:
        raise HTTPException(status_code=400, detail="Invalid image data")

@app.get("/health")
def health():
    return {"status": "ok"}

@app.post("/enroll")
async def enroll(req: EnrollRequest):
    img_path = base64_to_tempfile(req.image)
    try:
        # DeepFace.represent returns a list of representations
        objs = DeepFace.represent(img_path, model_name="VGG-Face", enforce_detection=True)
        if not objs:
            raise HTTPException(status_code=400, detail="No face detected")
        
        encoding = objs[0]["embedding"]
        db = load_db()
        db[req.user_id] = encoding
        save_db(db)
        return {"status": "success", "user_id": req.user_id}
    except Exception as e:
        raise HTTPException(status_code=400, detail=str(e))
    finally:
        if os.path.exists(img_path):
            os.remove(img_path)

@app.post("/recognize")
async def recognize(req: RecognizeRequest):
    img_path = base64_to_tempfile(req.image)
    db = load_db()
    
    if not db:
        raise HTTPException(status_code=401, detail="No faces enrolled")

    known_user_ids = list(db.keys())
    
    try:
        # We can use DeepFace.verify against each stored face or find best match
        # To match the previous logic of Euclidean distance:
        objs = DeepFace.represent(img_path, model_name="VGG-Face", enforce_detection=True)
        if not objs:
            raise HTTPException(status_code=401, detail="No face detected")
        
        unknown_encoding = np.array(objs[0]["embedding"])
        
        best_match = None
        min_dist = 1000
        
        # Use simple Euclidean distance for matching
        for uid in known_user_ids:
            known_encoding = np.array(db[uid])
            dist = np.linalg.norm(known_encoding - unknown_encoding)
            if dist < min_dist:
                min_dist = dist
                best_match = uid
        
        # Threshold for VGG-Face Euclidean distance is around 0.4 - 0.6
        if best_match and min_dist < 0.55:
            return {"user_id": best_match, "confidence": float(1 - min_dist)}
        
        raise HTTPException(status_code=401, detail="Face not recognized")
    except Exception as e:
        raise HTTPException(status_code=401, detail=str(e))
    finally:
        if os.path.exists(img_path):
            os.remove(img_path)

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="127.0.0.1", port=8001)
